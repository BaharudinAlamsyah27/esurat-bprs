<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ARCHIVE_TABLE = 'legacy_disposition_recipients_20261102';

    public function up(): void
    {
        if (! Schema::hasTable('disposition_recipients') || ! Schema::hasTable('dispositions')) {
            return;
        }

        $foreignKey = collect(Schema::getForeignKeys('disposition_recipients'))
            ->first(static function (array $foreignKey): bool {
                return in_array('disposition_id', $foreignKey['columns'] ?? [], true)
                    && ($foreignKey['foreign_table'] ?? null) === 'legacy_dispositions';
            });

        if (! is_array($foreignKey)) {
            return;
        }

        if (Schema::hasTable(self::ARCHIVE_TABLE)) {
            throw new RuntimeException('The legacy disposition recipient archive already exists; migration stopped to preserve data.');
        }

        // Keep every old row and its original schema before creating the corrected active table.
        Schema::rename('disposition_recipients', self::ARCHIVE_TABLE);

        Schema::create('disposition_recipients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('disposition_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('new');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->string('done_note', 255)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Use distinct names because the renamed archive retains its old FK names.
            $table->foreign('disposition_id', 'disposition_recipients_current_disposition_fk')
                ->references('id')->on('dispositions')->cascadeOnDelete();
            $table->foreign('user_id', 'disposition_recipients_current_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['disposition_id', 'user_id'], 'disposition_recipients_current_disposition_user_unique');
            $table->index(['user_id', 'status'], 'disposition_recipients_current_user_status_index');
        });

        $this->copyRecipientsThatMatchCurrentDispositions();
    }

    private function copyRecipientsThatMatchCurrentDispositions(): void
    {
        if (! Schema::hasTable('legacy_dispositions')
            || ! Schema::hasColumn('legacy_dispositions', 'incoming_letter_id')
            || ! Schema::hasColumn(self::ARCHIVE_TABLE, 'disposition_id')
        ) {
            return;
        }

        $hasUserId = Schema::hasColumn(self::ARCHIVE_TABLE, 'user_id');
        $hasLegacyUserId = Schema::hasColumn(self::ARCHIVE_TABLE, 'recipient_user_id');

        if (! $hasUserId && ! $hasLegacyUserId) {
            return;
        }

        $userExpression = $hasUserId && $hasLegacyUserId
            ? DB::raw('COALESCE(old_recipients.user_id, old_recipients.recipient_user_id) as mapped_user_id')
            : 'old_recipients.'.($hasUserId ? 'user_id' : 'recipient_user_id').' as mapped_user_id';

        $rows = DB::table(self::ARCHIVE_TABLE.' as old_recipients')
            ->join('legacy_dispositions as old_dispositions', 'old_dispositions.id', '=', 'old_recipients.disposition_id')
            ->join('dispositions as current_dispositions', 'current_dispositions.incoming_letter_id', '=', 'old_dispositions.incoming_letter_id')
            ->select('old_recipients.*', 'current_dispositions.id as current_disposition_id', $userExpression)
            ->orderBy('old_recipients.id')
            ->get();

        $recipients = [];

        foreach ($rows as $row) {
            $userId = $row->mapped_user_id ?? null;

            if (! is_numeric($userId) || ! DB::table('users')->where('id', $userId)->exists()) {
                continue;
            }

            $status = in_array($row->status ?? null, ['new', 'pending', 'read', 'in_progress', 'done'], true)
                ? $row->status
                : 'new';
            $key = $row->current_disposition_id.':'.$userId;
            $current = $recipients[$key] ?? null;

            if (is_array($current) && $this->statusRank($current['status']) >= $this->statusRank($status)) {
                continue;
            }

            $recipients[$key] = [
                'id' => $row->id,
                'disposition_id' => $row->current_disposition_id,
                'user_id' => $userId,
                'status' => $status,
                'read_at' => $row->read_at ?? null,
                'done_at' => $row->done_at ?? null,
                'done_note' => $this->doneNote($row),
                'notified_at' => $row->notified_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }

        foreach ($recipients as $recipient) {
            DB::table('disposition_recipients')->insert($recipient);
        }
    }

    private function doneNote(object $row): ?string
    {
        $note = $row->done_note ?? $row->report_notes ?? null;

        return is_string($note) ? mb_substr($note, 0, 255) : null;
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'done' => 3,
            'read', 'in_progress' => 2,
            default => 1,
        };
    }

    public function down(): void
    {
        // Keep both active and archived recipient records on rollback; do not risk deleting data.
    }
};
