<?php

namespace App\Filament\Resources;

use App\Enums\UserLevel;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Services\UserAdministrationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'DATA MASTER';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('m1.users');
    }

    public static function getModelLabel(): string
    {
        return __('m1.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.users');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
            TextInput::make('username')->label(__('m1.username'))->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(60),
            TextInput::make('email')->label(__('m1.email'))->email()->nullable()->unique(ignoreRecord: true)->maxLength(190),
            Select::make('level')
                ->label(__('m1.level'))
                ->options(collect(UserLevel::cases())->mapWithKeys(fn (UserLevel $level): array => [$level->value => $level->getLabel()])->all())
                ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                ->required(),
            TextInput::make('password')
                ->label(__('m1.password'))
                ->password()
                ->revealable()
                ->rule(Password::defaults())
                ->required()
                ->visibleOn('create'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('username')->label(__('m1.username'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('m1.email'))->searchable()->placeholder('—'),
                TextColumn::make('level')->label(__('m1.level'))->badge()->formatStateUsing(fn (UserLevel $state): string => $state->getLabel()),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([TernaryFilter::make('is_active')->label(__('m1.active_filter'))->trueLabel(__('m1.active'))->falseLabel(__('m1.inactive'))])
            ->headerActions([CreateAction::make()->label(__('app.create'))])
            ->actions([
                EditAction::make()->label(__('app.edit')),
                TableAction::make('toggleStatus')
                    ->label(fn (User $record): string => $record->is_active ? __('m1.deactivate') : __('m1.activate'))
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'warning' : 'success')
                    ->visible(fn (User $record): bool => ! $record->is(auth()->user()))
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? __('m1.deactivate_confirmation_title') : __('m1.activate_confirmation_title'))
                    ->modalDescription(fn (User $record): string => $record->is_active ? __('m1.deactivate_confirmation_body') : __('m1.activate_confirmation_body'))
                    ->action(function (User $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(UserAdministrationService::class)->toggleActive($actor, $record);
                    }),
                TableAction::make('resetPassword')
                    ->label(__('m1.reset_password'))
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->visible(fn (User $record): bool => ! $record->is(auth()->user()))
                    ->form([
                        TextInput::make('password')
                            ->label(__('m1.new_password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::defaults()),
                    ])
                    ->action(function (User $record, array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(UserAdministrationService::class)->resetPassword($actor, $record, (string) $data['password']);

                        Notification::make()->title(__('m1.password_reset_done'))->success()->send();
                    }),
                DeleteAction::make()
                    ->label(__('app.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('m1.delete_user_confirmation_title'))
                    ->modalDescription(__('m1.delete_user_confirmation_body'))
                    ->after(function (User $record): void {
                        activity('users')
                            ->causedBy(auth()->user())
                            ->event('user_deleted')
                            ->withProperties(['user_id' => $record->getKey(), 'username' => $record->username])
                            ->log(__('m1.audit_user_deleted'));
                    }),
            ])
            ->defaultSort('name')
            ->paginated([10, 25]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
