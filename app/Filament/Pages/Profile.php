<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rules\Password;

/**
 * @property Form $form
 */
class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.profile';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getSlug(): string
    {
        return 'profil-saya';
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
                TextInput::make('email')->label(__('m1.email'))->email()->nullable()->unique(table: 'users', ignorable: auth()->user())->maxLength(190),
                TextInput::make('current_password')->label(__('m1.current_password'))->password()->revealable()->nullable()->requiredWith('password')->currentPassword(),
                TextInput::make('password')->label(__('m1.new_password'))->password()->revealable()->nullable()->rule(Password::defaults())->confirmed()->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('password_confirmation')->label(__('m1.password_confirmation'))->password()->revealable()->nullable()->dehydrated(false),
            ])
            ->statePath('data');
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('m1.save'))->submit('save')];
    }

    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $state = $this->form->getState();
        $passwordChanged = filled($state['password'] ?? null);

        $user->fill(array_intersect_key($state, array_flip(['name', 'email', 'password'])))->save();

        if ($passwordChanged) {
            activity('users')
                ->causedBy($user)
                ->performedOn($user)
                ->event('profile_password_changed')
                ->log(__('m1.audit_profile_password_changed'));
        }

        Notification::make()->title(__('m1.profile_saved'))->success()->send();
    }
}
