<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\OutgoingLetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** @property int $created_by */
class OutgoingLetter extends Model
{
    /** @use HasFactory<OutgoingLetterFactory> */
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

            activity('outgoing_letter')
                ->causedBy(auth()->user())
                ->performedOn($letter)
                ->event('updated')
                ->withProperties(['changed_fields' => $changedFields])
                ->log(__('outgoing.audit_updated'));
        });
    }

    protected $fillable = [
        'agenda_year', 'agenda_seq', 'agenda_number', 'letter_number', 'recipient', 'subject',
        'note', 'classification_id', 'letter_date', 'sent_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'agenda_year' => 'integer',
            'agenda_seq' => 'integer',
            'letter_date' => 'date',
            'sent_date' => 'date',
        ];
    }

    /** @return BelongsTo<Classification, $this> */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['agenda_number', 'letter_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
