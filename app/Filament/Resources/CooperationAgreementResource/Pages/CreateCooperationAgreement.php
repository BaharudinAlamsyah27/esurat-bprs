<?php

namespace App\Filament\Resources\CooperationAgreementResource\Pages;

use App\Filament\Resources\CooperationAgreementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCooperationAgreement extends CreateRecord
{
    protected static string $resource = CooperationAgreementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CooperationAgreementResource::saveRecord($data);
    }
}
