<?php

namespace App\Filament\Resources\CooperationAgreementResource\Pages;

use App\Filament\Resources\CooperationAgreementResource;
use App\Models\CooperationAgreement;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class EditCooperationAgreement extends EditRecord
{
    protected static string $resource = CooperationAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()
                ->label(__('app.delete'))
                ->modalHeading(__('cooperation.delete_confirmation_title'))
                ->modalDescription(__('cooperation.delete_confirmation_body'))
                ->modalSubmitActionLabel(__('app.confirm_delete'))
                ->modalCancelActionLabel(__('app.cancel')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof CooperationAgreement) {
            throw new LogicException('Unexpected record type for cooperation agreement.');
        }

        return CooperationAgreementResource::saveRecord($data, $record);
    }
}
