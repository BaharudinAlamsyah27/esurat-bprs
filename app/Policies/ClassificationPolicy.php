<?php

namespace App\Policies;

use App\Models\Classification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClassificationPolicy extends MasterDataPolicy
{
    public function delete(User $user, Model $record): bool
    {
        return $record instanceof Classification
            && parent::delete($user, $record)
            && $record->usageCount() === 0;
    }
}
