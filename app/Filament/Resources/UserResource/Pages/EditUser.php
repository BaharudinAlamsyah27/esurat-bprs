<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserLevel;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        return [
            Action::make('toggleStatus')
                ->label($record->is_active ? __('m1.deactivate') : __('m1.activate'))
                ->icon($record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                ->color($record->is_active ? 'warning' : 'success')
                ->visible(! $record->is(auth()->user()))
                ->requiresConfirmation()
                ->modalHeading($record->is_active ? __('m1.deactivate_confirmation_title') : __('m1.activate_confirmation_title'))
                ->modalDescription($record->is_active ? __('m1.deactivate_confirmation_body') : __('m1.activate_confirmation_body'))
                ->action(function () use ($record): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserAdministrationService::class)->toggleActive($actor, $record);
                }),
            Action::make('resetPassword')
                ->label(__('m1.reset_password'))
                ->icon('heroicon-o-key')
                ->visible(! $record->is(auth()->user()))
                ->form([
                    TextInput::make('password')
                        ->label(__('m1.new_password'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::defaults()),
                ])
                ->action(function (array $data) use ($record): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserAdministrationService::class)->resetPassword($actor, $record, (string) $data['password']);
                    Notification::make()->title(__('m1.password_reset_done'))->success()->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()
                ->label(__('app.delete'))
                ->modalHeading(__('m1.delete_user_confirmation_title'))
                ->modalDescription(__('m1.delete_user_confirmation_body'))
                ->modalSubmitActionLabel(__('app.confirm_delete'))
                ->modalCancelActionLabel(__('app.cancel'))
                ->disabled(fn (User $record): bool => $record->hasUsageHistory())
                ->tooltip(fn (User $record): ?string => $record->hasUsageHistory() ? __('m1.user_delete_blocked') : null)
                ->before(fn (User $record) => abort_unless(auth()->user()?->can('delete', $record), 403))
                ->after(function (User $record): void {
                    activity('users')
                        ->causedBy(auth()->user())
                        ->event('user_deleted')
                        ->withProperties(['user_id' => $record->getKey(), 'username' => $record->username])
                        ->log(__('m1.audit_user_deleted'));
                }),
        ];
    }

    protected function configureAction(Action $action): void
    {
        parent::configureAction($action);

        if ($action instanceof DeleteAction) {
            $action->authorize(fn (): bool => auth()->user()?->isAdmin() ?? false);
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        $currentLevel = (string) $record->getRawOriginal('level');
        $newLevel = (string) ($data['level'] ?? $currentLevel);
        if ($record->is(auth()->user()) && $newLevel !== $currentLevel) {
            throw ValidationException::withMessages([
                'data.level' => __('m1.cannot_change_own_level'),
            ]);
        }

        $removesLastActiveAdmin = $record->isAdmin()
            && $record->is_active
            && ((string) $newLevel !== UserLevel::Admin->value || ! (bool) ($data['is_active'] ?? $record->is_active))
            && User::query()
                ->where('level', UserLevel::Admin->value)
                ->where('is_active', true)
                ->count() <= 1;

        if ($removesLastActiveAdmin) {
            throw ValidationException::withMessages([
                'data.level' => __('m1.last_active_admin'),
            ]);
        }

        return $data;
    }
}
