<?php

namespace App\Filament\Resources;

use App\Models\Classification;
use App\Models\DispositionInstruction;
use App\Models\LetterType;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class MasterDataResource extends Resource
{
    protected static ?string $navigationGroup = 'PENGELOLAAN';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('master.manage') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::schemaForModel());
    }

    public static function table(Table $table): Table
    {
        $model = static::getModel();
        $columns = match ($model) {
            Unit::class => [
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('m1.code'))->searchable()->sortable(),
                TextColumn::make('type')->label(__('m1.unit_type'))->badge(),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean(),
            ],
            Position::class => [
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('level')->label(__('m1.level'))->sortable(),
                IconColumn::make('is_signer_eligible')->label(__('m1.signer_eligible'))->boolean(),
            ],
            Classification::class => [
                TextColumn::make('code')->label(__('m1.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('retention_years')->label(__('m1.retention_years')),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean(),
            ],
            LetterType::class => [
                TextColumn::make('code')->label(__('m1.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('category')->label(__('m1.category'))->badge(),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean(),
            ],
            DispositionInstruction::class => [
                TextColumn::make('text')->label(__('m1.instruction'))->searchable()->sortable(),
                TextColumn::make('sort_order')->label(__('m1.sort_order'))->sortable(),
                IconColumn::make('is_active')->label(__('m1.active'))->boolean(),
            ],
            default => [
                TextColumn::make('key')->label(__('m1.key'))->searchable()->sortable(),
                TextColumn::make('updated_at')->label(__('m1.updated_at'))->dateTime('d M Y H:i'),
            ],
        };

        return $table
            ->columns($columns)
            ->defaultSort($model === DispositionInstruction::class ? 'sort_order' : ($model === Setting::class ? 'key' : 'name'))
            ->actions([
                EditAction::make()->label(__('app.edit')),
                DeleteAction::make()->label(__('app.delete'))->requiresConfirmation(),
            ])
            ->paginated([10, 25]);
    }

    /** @return array<int, mixed> */
    protected static function schemaForModel(): array
    {
        return match (static::getModel()) {
            Unit::class => [
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
                TextInput::make('code')->label(__('m1.code'))->required()->maxLength(30)->unique(ignoreRecord: true),
                Select::make('type')->label(__('m1.unit_type'))->options(__('m1.unit_types'))->required(),
                Select::make('parent_id')->label(__('m1.parent_unit'))->relationship('parent', 'name')->searchable()->preload(),
                Select::make('head_user_id')->label(__('m1.unit_head'))->relationship('head', 'name')->searchable()->preload(),
                Toggle::make('is_active')->label(__('m1.active'))->default(true),
            ],
            Position::class => [
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120)->unique(ignoreRecord: true),
                TextInput::make('level')->label(__('m1.level'))->numeric()->integer()->minValue(1)->maxValue(999)->required(),
                Toggle::make('is_signer_eligible')->label(__('m1.signer_eligible'))->default(false),
            ],
            Classification::class => [
                TextInput::make('code')->label(__('m1.code'))->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
                Select::make('parent_id')->label(__('m1.parent_classification'))->relationship('parent', 'name')->searchable()->preload(),
                TextInput::make('retention_years')->label(__('m1.retention_years'))->numeric()->integer()->minValue(0),
                Toggle::make('is_active')->label(__('m1.active'))->default(true),
            ],
            LetterType::class => [
                TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
                TextInput::make('code')->label(__('m1.code'))->required()->maxLength(30)->unique(ignoreRecord: true),
                Select::make('category')->label(__('m1.category'))->options(__('m1.letter_categories'))->required(),
                Toggle::make('is_active')->label(__('m1.active'))->default(true),
            ],
            DispositionInstruction::class => [
                TextInput::make('text')->label(__('m1.instruction'))->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('sort_order')->label(__('m1.sort_order'))->numeric()->integer()->minValue(0)->required(),
                Toggle::make('is_active')->label(__('m1.active'))->default(true),
            ],
            default => [
                TextInput::make('key')->label(__('m1.key'))->required()->maxLength(120)->unique(ignoreRecord: true),
                Textarea::make('value')
                    ->label(__('m1.json_value'))
                    ->rows(5)
                    ->rules(['json'])
                    ->formatStateUsing(static function (mixed $state): string {
                        if (! is_string($state)) {
                            return '';
                        }

                        $decoded = json_decode($state, true);

                        return json_last_error() === JSON_ERROR_NONE
                            ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : $state;
                    })
                    ->dehydrateStateUsing(static fn (string $state): string => json_encode(
                        json_decode($state, true, 512, JSON_THROW_ON_ERROR),
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                    ))
                    ->afterStateHydrated(static function (Textarea $component, mixed $state): void {
                        if (is_string($state)) {
                            $component->state((string) json_encode(
                                json_decode($state, true, 512, JSON_THROW_ON_ERROR),
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                            ));
                        }
                    }),
            ],
        };
    }
}
