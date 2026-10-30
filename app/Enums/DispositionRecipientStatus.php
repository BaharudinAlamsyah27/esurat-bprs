<?php

namespace App\Enums;

enum DispositionRecipientStatus: string
{
    case New = 'new';
    case Pending = 'pending'; // Kompatibilitas data lama
    case Read = 'read';
    case InProgress = 'in_progress'; // Kompatibilitas data lama
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::New, self::Pending => __('disposition.status_new'),
            self::Read => __('disposition.status_read'),
            self::InProgress => 'Diproses',
            self::Done => __('disposition.status_done'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::Pending => 'warning',
            self::Read, self::InProgress => 'info',
            self::Done => 'success',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Done;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::New->value => __('disposition.status_new'),
            self::Read->value => __('disposition.status_read'),
            self::Done->value => __('disposition.status_done'),
        ];
    }
}
