<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property DispositionStatus $status
 * @property Carbon|null $due_at
 */
class Disposition extends Model
{
    use LogsActivity;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = ['incoming_letter_id', 'parent_id', 'from_user_id', 'notes', 'urgency', 'due_at', 'status', 'closed_at', 'closed_by'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'closed_at' => 'datetime', 'status' => DispositionStatus::class];
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<Disposition, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Disposition, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /** @return HasMany<DispositionRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class);
    }

    /** @return BelongsToMany<DispositionInstruction, $this> */
    public function instructions(): BelongsToMany
    {
        return $this->belongsToMany(DispositionInstruction::class, 'disposition_disposition_instruction');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['incoming_letter_id', 'parent_id', 'from_user_id', 'status', 'due_at', 'closed_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
