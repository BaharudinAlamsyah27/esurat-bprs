<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'ui_font_scale' => $user->ui_font_scale ?? 'normal',
            'signature_path' => $user->signature_path,
            'paraf_path' => $user->paraf_path,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
                TextInput::make('username')->label(__('m1.username'))->required()->alphaDash()->unique(table: 'users', ignorable: auth()->user())->maxLength(60),
                TextInput::make('email')->label(__('m1.email'))->email()->required()->unique(table: 'users', ignorable: auth()->user())->maxLength(190),
                TextInput::make('phone')->label(__('m1.phone'))->tel()->maxLength(30),
                TextInput::make('password')->label(__('m1.password'))->password()->revealable()->nullable()->rule(Password::defaults())->dehydrated(fn (?string $state): bool => filled($state))->helperText(__('m1.password_optional')),
                Select::make('ui_font_scale')->label(__('app.font_size'))->options(['normal' => __('app.font_normal'), 'large' => __('app.font_large'), 'largest' => __('app.font_largest')])->required(),
                FileUpload::make('signature_path')->label(__('m1.signature'))->disk('private')->directory('signatures')->visibility('private')->image()->acceptedFileTypes(['image/png'])->maxSize(2048)->helperText(__('m1.signature_help')),
                FileUpload::make('paraf_path')->label(__('m1.paraf'))->disk('private')->directory('signatures')->visibility('private')->image()->acceptedFileTypes(['image/png'])->maxSize(2048)->helperText(__('m1.signature_help')),
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

        $user->fill(array_intersect_key($state, array_flip([
            'name', 'username', 'email', 'phone', 'ui_font_scale', 'signature_path', 'paraf_path', 'password',
        ])))->save();

        Notification::make()->title(__('m1.profile_saved'))->success()->send();
    }
}
