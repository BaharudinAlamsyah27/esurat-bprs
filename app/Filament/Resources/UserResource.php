<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'PENGELOLAAN';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('user.manage') ?? false;
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
            TextInput::make('nip')->label(__('m1.nip'))->unique(ignoreRecord: true)->maxLength(40),
            TextInput::make('email')->label(__('m1.email'))->email()->required()->unique(ignoreRecord: true)->maxLength(190),
            TextInput::make('phone')->label(__('m1.phone'))->tel()->maxLength(30),
            Select::make('unit_id')->label(__('m1.unit_id'))->relationship('unit', 'name')->searchable()->preload(),
            Select::make('position_id')->label(__('m1.position_id'))->relationship('position', 'name')->searchable()->preload(),
            Select::make('roles')->label(__('m1.roles'))->relationship('roles', 'name')->getOptionLabelFromRecordUsing(fn (Role $record): string => (string) __('m1.role_labels.'.$record->name))->multiple()->preload()->required()->visible(fn (): bool => auth()->user()?->can('user.manage') ?? false),
            TextInput::make('password')->label(__('m1.password'))->password()->revealable()->rule(Password::defaults())->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
            Toggle::make('is_active')->label(__('m1.active'))->default(true)->visible(fn (): bool => auth()->user()?->can('user.manage') ?? false),
            FileUpload::make('signature_path')->label(__('m1.signature'))->disk('private')->directory('signatures')->visibility('private')->image()->acceptedFileTypes(['image/png'])->maxSize(2048)->helperText(__('m1.signature_help')),
            FileUpload::make('paraf_path')->label(__('m1.paraf'))->disk('private')->directory('signatures')->visibility('private')->image()->acceptedFileTypes(['image/png'])->maxSize(2048)->helperText(__('m1.signature_help')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('nip')->label(__('m1.nip'))->searchable(),
                TextColumn::make('email')->label(__('m1.email'))->searchable(),
                TextColumn::make('unit.name')->label(__('m1.unit_id'))->toggleable(),
                TextColumn::make('position.name')->label(__('m1.position_id'))->toggleable(),
                TextColumn::make('roles.name')->label(__('m1.roles'))->badge(),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label(__('m1.active_filter'))->trueLabel(__('m1.active'))->falseLabel(__('m1.inactive'))])
            ->headerActions([CreateAction::make()->label(__('app.create'))])
            ->actions([EditAction::make()->label(__('app.edit'))->requiresConfirmation()])
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
