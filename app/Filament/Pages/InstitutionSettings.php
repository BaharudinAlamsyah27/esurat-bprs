<?php

namespace App\Filament\Pages;

use App\Filament\Support\ThemePalette;
use App\Models\Institution;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

/**
 * @property Form $form
 */
class InstitutionSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.institution-settings';

    protected static ?string $navigationGroup = 'DATA MASTER';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('m1.institution');
    }

    public function mount(): void
    {
        /** @var Institution $institution */
        $institution = Institution::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'BPRS Sragen', 'city' => 'Sragen'],
        );

        $this->form->fill([
            ...$institution->attributesToArray(),
            'theme_color' => ThemePalette::selected(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('m1.institution_identity'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label(__('m1.institution_name'))->required()->maxLength(160),
                        TextInput::make('city')->label(__('m1.city'))->maxLength(120),
                        Textarea::make('address')->label(__('m1.address'))->rows(3)->maxLength(2000)->columnSpanFull(),
                        TextInput::make('phone')->label(__('m1.phone'))->tel()->maxLength(40),
                        TextInput::make('email')->label(__('m1.email'))->email()->maxLength(190),
                        FileUpload::make('logo_path')
                            ->label(__('m1.logo'))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->disk('private')
                            ->directory('institution/logos')
                            ->visibility('private')
                            ->helperText(__('m1.logo_help'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('m1.institution_leader'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('leader_name')->label(__('m1.leader_name'))->maxLength(120),
                        TextInput::make('leader_position')->label(__('m1.leader_position'))->maxLength(120),
                    ]),
                Section::make(__('m1.appearance'))
                    ->schema([
                        Select::make('theme_color')
                            ->label(__('m1.theme_color'))
                            ->options(ThemePalette::options())
                            ->in(array_keys(ThemePalette::options()))
                            ->required()
                            ->helperText(__('m1.theme_color_help')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $themeColor = $state['theme_color'];
        unset($state['theme_color']);
        $oldLogo = Institution::query()->whereKey(1)->value('logo_path');

        Institution::query()->updateOrCreate(['id' => 1], $state);
        Setting::query()->updateOrCreate(['key' => 'ui_theme_color'], ['value' => ['color' => $themeColor]]);

        $newLogo = $state['logo_path'] ?? null;
        if (is_string($oldLogo) && $oldLogo !== '' && $oldLogo !== $newLogo) {
            Storage::disk('private')->delete($oldLogo);
        }

        Notification::make()->title(__('m1.institution_saved'))->success()->send();
    }
}
