<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    use LogsActivity;
    use RecordsActivity;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $encodedValue = static::query()->where('key', $key)->value('value');

        if ($encodedValue === null) {
            return $default;
        }

        try {
            return json_decode($encodedValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $default;
        }
    }
}
