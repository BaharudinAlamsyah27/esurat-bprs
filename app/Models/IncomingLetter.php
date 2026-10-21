<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\IncomingLetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $registered_by
 * @property int $agenda_year
 * @property int $agenda_seq
 */
class IncomingLetter extends Model
{
    /** @use HasFactory<IncomingLetterFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (self $letter): void {
            $changedFields = array_values(array_diff(array_keys($letter->getChanges()), ['updated_at']));

            if ($changedFields === []) {
                return;
            }

            activity('incoming_letter')
                ->causedBy(auth()->user())
                ->performedOn($letter)
                ->event('updated')
                ->withProperties(['changed_fields' => $changedFields])
                ->log(__('incoming.audit_updated'));
        });
    }

    protected $fillable = [
        'agenda_year', 'agenda_seq', 'agenda_number', 'received_at', 'letter_date', 'letter_number',
        'sender_name', 'subject', 'note', 'classification_id', 'status', 'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'agenda_year' => 'integer',
            'agenda_seq' => 'integer',
            'received_at' => 'date',
            'letter_date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** @return BelongsTo<Classification, $this> */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return HasOne<Disposition, $this> */
    public function disposition(): HasOne
    {
        return $this->hasOne(Disposition::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['agenda_number', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
