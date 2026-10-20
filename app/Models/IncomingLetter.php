<?php

namespace App\Models;

use App\Enums\IncomingDeliveryMethod;
use App\Enums\IncomingLetterStatus;
use App\Enums\IncomingLetterUrgency;
use App\Enums\LetterConfidentiality;
use App\Models\Concerns\VisibleToUserScope;
use Database\Factories\IncomingLetterFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property IncomingLetterStatus $status
 * @property LetterConfidentiality $confidentiality
 * @property IncomingLetterUrgency $urgency
 * @property IncomingDeliveryMethod $delivery_method
 * @property int $registered_by
 * @property int|null $initial_recipient_id
 * @property int $agenda_year
 * @property int $agenda_seq
 */
#[ScopedBy([VisibleToUserScope::class])]
class IncomingLetter extends Model
{
    /** @use HasFactory<IncomingLetterFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'agenda_year', 'agenda_seq', 'agenda_number', 'received_at', 'letter_date', 'letter_number',
        'sender_name', 'sender_institution', 'subject', 'summary', 'letter_type_id', 'classification_id',
        'urgency', 'confidentiality', 'delivery_method', 'attachments_count', 'status', 'registered_by',
        'initial_recipient_id', 'completed_at', 'completion_note', 'archive_location', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'agenda_year' => 'integer',
            'agenda_seq' => 'integer',
            'received_at' => 'date',
            'letter_date' => 'date',
            'attachments_count' => 'integer',
            'urgency' => IncomingLetterUrgency::class,
            'confidentiality' => LetterConfidentiality::class,
            'delivery_method' => IncomingDeliveryMethod::class,
            'status' => IncomingLetterStatus::class,
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** @return BelongsTo<User, $this> */
    public function initialRecipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initial_recipient_id');
    }

    /** @return BelongsTo<LetterType, $this> */
    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
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

    public function isDirectlyInvolved(User $user): bool
    {
        return $this->registered_by === $user->id || $this->initial_recipient_id === $user->id;
    }

    public function isMetadataOnlyFor(mixed $user): bool
    {
        return $user instanceof User
            && $user->hasRole('admin_persuratan')
            && $this->confidentiality !== LetterConfidentiality::Ordinary
            && ! $this->isDirectlyInvolved($user);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['agenda_number', 'status', 'initial_recipient_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
