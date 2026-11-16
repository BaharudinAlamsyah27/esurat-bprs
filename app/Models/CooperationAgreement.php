<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\CooperationAgreementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $created_by
 * @property int|null $updated_by
 * @property Carbon $agreement_date
 */
class CooperationAgreement extends Model
{
    /** @use HasFactory<CooperationAgreementFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (self $agreement): void {
            $changedFields = array_values(array_diff(array_keys($agreement->getChanges()), ['updated_at', 'updated_by']));

            if ($changedFields === []) {
                return;
            }

            activity('cooperation_agreement')
                ->causedBy(auth()->user())
                ->performedOn($agreement)
                ->event('updated')
                ->withProperties(['changed_fields' => $changedFields])
                ->log(__('cooperation.audit_updated'));
        });
    }

    protected $fillable = [
        'bss_pks_number',
        'partner_pks_number',
        'partner_npwp',
        'agreement_date',
        'partner_name',
        'subject',
        'pic_name',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'agreement_date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function pksAttachment(): ?Attachment
    {
        return $this->attachments()->where('kind', 'pks_file')->latest('id')->first();
    }

    public function legalityAttachment(): ?Attachment
    {
        return $this->attachments()->where('kind', 'partner_legality')->latest('id')->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bss_pks_number', 'partner_pks_number', 'partner_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
