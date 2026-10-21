<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;

trait RecordsActivity
{
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => __('m1.audit_model_'.$eventName))
            ->dontSubmitEmptyLogs();
    }
}
