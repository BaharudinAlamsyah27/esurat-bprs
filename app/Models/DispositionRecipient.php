<?php

namespace App\Models;

use App\Enums\DispositionRecipientStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property DispositionRecipientStatus $status
 * @property int $disposition_id
 * @property int|null $recipient_user_id
 * @property int|null $recipient_unit_id
 * @property Carbon|null $started_at
 */
class DispositionRecipient extends Model
{
    use LogsActivity;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = ['disposition_id', 'recipient_user_id', 'recipient_unit_id', 'status', 'read_at', 'started_at', 'done_at', 'report_notes', 'acting_for_user_id'];

    protected function casts(): array
    {
        return ['status' => DispositionRecipientStatus::class, 'read_at' => 'datetime', 'started_at' => 'datetime', 'done_at' => 'datetime'];
    }

    /** @return BelongsTo<Disposition, $this> */
    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'recipient_unit_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['disposition_id', 'status', 'report_notes', 'done_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
