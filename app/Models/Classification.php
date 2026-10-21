<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\ClassificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Classification extends Model
{
    /** @use HasFactory<ClassificationFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['code', 'name', 'description'];
}
