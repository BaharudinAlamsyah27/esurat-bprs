<?php

namespace App\Models;

use App\Enums\DispositionRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $disposition_id
 * @property int $user_id
 * @property DispositionRecipientStatus $status
 * @property Carbon|null $read_at
 * @property Carbon|null $done_at
 * @property string|null $done_note
 * @property Carbon|null $notified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Disposition $disposition
 * @property-read User $user
 */
class DispositionRecipient extends Model
{
    protected $fillable = [
        'disposition_id',
        'user_id',
        'status',
        'read_at',
        'done_at',
        'done_note',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DispositionRecipientStatus::class,
            'read_at' => 'datetime',
            'done_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Disposition, $this> */
    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if ($this->status === DispositionRecipientStatus::New || $this->status === DispositionRecipientStatus::Pending) {
            $this->update([
                'status' => DispositionRecipientStatus::Read,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsDone(?string $note = null): void
    {
        $this->update([
            'status' => DispositionRecipientStatus::Done,
            'done_at' => now(),
            'done_note' => $note,
        ]);
    }
}
