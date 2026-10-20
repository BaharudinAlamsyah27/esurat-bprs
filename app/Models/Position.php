<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['name', 'level', 'is_signer_eligible'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'is_signer_eligible' => 'boolean'];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
