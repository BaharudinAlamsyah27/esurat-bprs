<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Institution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'city',
        'logo_path',
        'leader_name',
        'leader_position',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'address', 'phone', 'email', 'city', 'leader_name', 'leader_position'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => __('m1.audit_model_'.$eventName))
            ->dontSubmitEmptyLogs();
    }
}
