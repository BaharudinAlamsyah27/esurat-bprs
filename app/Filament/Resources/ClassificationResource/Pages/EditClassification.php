<?php

namespace App\Filament\Resources\ClassificationResource\Pages;

use App\Filament\Resources\ClassificationResource;
use App\Models\Classification;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassification extends EditRecord
{
    protected static string $resource = ClassificationResource::class;

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
                ->modalHeading(__('m1.delete_classification_confirmation_title'))
                ->modalDescription(__('m1.delete_classification_confirmation_body'))
                ->modalSubmitActionLabel(__('app.confirm_delete'))
                ->modalCancelActionLabel(__('app.cancel'))
                ->disabled(fn (Classification $record): bool => $record->usageCount() > 0)
                ->tooltip(fn (Classification $record): ?string => $record->usageCount() > 0
                    ? __('m1.classification_delete_blocked', ['count' => $record->usageCount()])
                    : null)
                ->before(fn (Classification $record) => abort_unless(auth()->user()?->can('delete', $record), 403)),
        ];
    }

    protected function configureAction(Action $action): void
    {
        parent::configureAction($action);

        if ($action instanceof DeleteAction) {
            $action->authorize(fn (): bool => auth()->user()?->isAdmin() ?? false);
        }
    }
}
