<?php

namespace App\Policies;

use App\Models\Classification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassificationPolicy extends MasterDataPolicy
{
    public function delete(User $user, Model $record): bool
    {
        if (! $record instanceof Classification || ! parent::delete($user, $record) || $record->incomingLetters()->exists()) {
            return false;
        }

        return ! Schema::hasTable('outgoing_letters')
            || ! DB::table('outgoing_letters')->where('classification_id', $record->getKey())->exists();
    }
}
