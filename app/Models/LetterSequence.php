<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterSequence extends Model
{
    protected $fillable = ['category', 'scope_key', 'last_number'];

    protected function casts(): array
    {
        return ['last_number' => 'integer'];
    }
}
