<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\DispositionInstructionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DispositionInstruction extends Model
{
    /** @use HasFactory<DispositionInstructionFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['text', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
