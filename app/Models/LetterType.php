<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\LetterTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class LetterType extends Model
{
    /** @use HasFactory<LetterTypeFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['name', 'code', 'category', 'default_approval_flow_id', 'is_active'];

    protected function casts(): array
    {
        return ['default_approval_flow_id' => 'integer', 'is_active' => 'boolean'];
    }
}
