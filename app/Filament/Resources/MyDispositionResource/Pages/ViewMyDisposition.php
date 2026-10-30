<?php

namespace App\Filament\Resources\MyDispositionResource\Pages;

use App\Actions\MarkDispositionRead;
use App\Filament\Resources\MyDispositionResource;
use App\Models\DispositionRecipient;
use Filament\Resources\Pages\ViewRecord;

class ViewMyDisposition extends ViewRecord
{
    protected static string $resource = MyDispositionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = auth()->user();
        $record = $this->getRecord();

        if ($user && $record instanceof DispositionRecipient) {
            app(MarkDispositionRead::class)->execute($user, $record);
        }

        return $data;
    }
}
