<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\ClassificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Classification extends Model
{
    /** @use HasFactory<ClassificationFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['code', 'name', 'parent_id', 'retention_years', 'is_active'];

    protected function casts(): array
    {
        return ['retention_years' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<Classification, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Classification, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
