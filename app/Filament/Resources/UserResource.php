<?php

namespace App\Filament\Resources;

use App\Enums\UserLevel;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
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

    protected static ?int $navigationSort = 3;

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
            TextInput::make('position_title')->label(__('m1.position_title'))->placeholder('Contoh: Staf Operasional')->maxLength(100),
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
            Placeholder::make('delete_blocked_reason')
                ->label(__('app.delete'))
                ->content(__('m1.user_delete_blocked'))
                ->visible(fn (string $operation, ?User $record): bool => $operation === 'edit' && ($record?->hasUsageHistory() ?? false))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder(__('m1.user_search_hint'))
            ->columns([
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable()->placeholder('—'),
                TextColumn::make('username')->label(__('m1.username'))->searchable()->placeholder('—'),
                TextColumn::make('email')->label(__('m1.email'))->searchable()->placeholder('—'),
                TextColumn::make('level')->label(__('m1.level'))->badge()->formatStateUsing(fn (UserLevel $state): string => $state->getLabel()),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([TernaryFilter::make('is_active')->label(__('m1.active_filter'))->trueLabel(__('m1.active'))->falseLabel(__('m1.inactive'))])
            ->filtersTriggerAction(fn (Action $action, Table $table): Action => $action->extraAttributes([
                'data-active-filters' => $table->getActiveFiltersCount(),
            ]))
            ->actions([
                EditAction::make()->label(__('app.edit'))->icon('heroicon-o-pencil-square'),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateActions([
                CreateAction::make()->label(__('m1.add_user'))->icon('heroicon-o-plus'),
            ])
            ->emptyStateHeading(__('app.no_data'));
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
