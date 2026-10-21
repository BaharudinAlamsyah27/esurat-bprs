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
            ->dontSubmitEmptyLogs();
    }
}
