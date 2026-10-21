#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

BACKUP_ENV_FILE="${BACKUP_ENV_FILE:-/etc/esurat/backup.env}"
if [[ -r "$BACKUP_ENV_FILE" ]]; then
    set -a
    # This file is a restricted shell-style file maintained by the server administrator.
    source "$BACKUP_ENV_FILE"
    set +a
fi

APP_ROOT="${APP_ROOT:-/var/www/esurat}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/esurat}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
: "${DB_DATABASE:?Set DB_DATABASE in the protected backup environment file}"
: "${DB_USERNAME:?Set DB_USERNAME in the protected backup environment file}"
DB_PASSWORD="${DB_PASSWORD:-}"

for binary in mysqldump tar gzip find; do
    command -v "$binary" >/dev/null 2>&1 || { printf 'Required command not found: %s\n' "$binary" >&2; exit 1; }
done

mkdir -p "$BACKUP_DIR" "$APP_ROOT/storage/app/private"
chmod 700 "$BACKUP_DIR"
WORK_DIR="$(mktemp -d)"
MYSQL_DEFAULTS="$(mktemp)"
cleanup() {
    rm -rf -- "$WORK_DIR"
    rm -f -- "$MYSQL_DEFAULTS"
}
trap cleanup EXIT
chmod 600 "$MYSQL_DEFAULTS"

escape_mysql_value() {
    local value="$1"
    value="${value//\\/\\\\}"
    value="${value//\"/\\\"}"
    printf '%s' "$value"
}

{
    printf '[client]\n'
    printf 'host="%s"\n' "$(escape_mysql_value "$DB_HOST")"
    printf 'port="%s"\n' "$(escape_mysql_value "$DB_PORT")"
    printf 'user="%s"\n' "$(escape_mysql_value "$DB_USERNAME")"
    printf 'password="%s"\n' "$(escape_mysql_value "$DB_PASSWORD")"
} > "$MYSQL_DEFAULTS"

STAMP="$(date '+%Y%m%d-%H%M%S')"
DB_DUMP="$WORK_DIR/database.sql"
mysqldump --defaults-extra-file="$MYSQL_DEFAULTS" --single-transaction --routines --triggers --events --default-character-set=utf8mb4 "$DB_DATABASE" > "$DB_DUMP"
gzip "$DB_DUMP"
tar -czf "$BACKUP_DIR/esurat-$STAMP.tar.gz" -C "$WORK_DIR" database.sql.gz -C "$APP_ROOT" storage/app/private
chmod 600 "$BACKUP_DIR/esurat-$STAMP.tar.gz"
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'esurat-*.tar.gz' -mtime +29 -delete

printf 'Backup created: %s/esurat-%s.tar.gz\n' "$BACKUP_DIR" "$STAMP"
