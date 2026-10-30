<?php

namespace App\Models;

use App\Enums\UserLevel;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use LogsActivity;
    use Notifiable;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'position_title',
        'username',
        'email',
        'level',
        'is_active',
        'push_enabled',
        'last_login_at',
        'ui_font_scale',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $panel->getId() === 'admin';
    }

    public function isAdmin(): bool
    {
        return $this->getRawOriginal('level') === UserLevel::Admin->value;
    }

    public function hasUsageHistory(): bool
    {
        return Activity::query()
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('causer_type', self::class)->where('causer_id', $this->getKey()))
                ->orWhere(fn ($query) => $query
                    ->where('subject_type', self::class)
                    ->where('subject_id', $this->getKey())
                    ->where('event', '!=', 'created')))
            ->exists()
            || DB::table('incoming_letters')->where('registered_by', $this->getKey())->exists()
            || DB::table('outgoing_letters')->where('created_by', $this->getKey())->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'level' => UserLevel::class,
            'is_active' => 'boolean',
            'push_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<DeviceToken, $this> */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /** @return HasMany<DispositionRecipient, $this> */
    public function dispositionRecipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'level', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => __('m1.audit_model_'.$eventName))
            ->dontSubmitEmptyLogs();
    }
}
