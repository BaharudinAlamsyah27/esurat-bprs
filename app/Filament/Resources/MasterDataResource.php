<?php

namespace App\Filament\Resources;

use App\Models\Classification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class MasterDataResource extends Resource
{
    protected static ?string $navigationGroup = 'DATA MASTER';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->label(__('m1.code'))->required()->maxLength(30)->unique(ignoreRecord: true),
            TextInput::make('name')->label(__('m1.name'))->required()->maxLength(120),
            Textarea::make('description')->label(__('m1.description'))->rows(3)->maxLength(1000),
            Placeholder::make('delete_blocked_reason')
                ->label(__('app.delete'))
                ->content(fn (?Classification $record): string => __('m1.classification_delete_blocked', ['count' => $record?->usageCount() ?? 0]))
                ->visible(fn (string $operation, ?Classification $record): bool => $operation === 'edit' && ($record?->usageCount() ?? 0) > 0)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('m1.code'))->searchable()->sortable()->placeholder('—'),
                TextColumn::make('name')->label(__('m1.name'))->searchable()->placeholder('—'),
                TextColumn::make('description')->label(__('m1.description'))->limit(60)->placeholder('—'),
            ])
            ->actions([
                EditAction::make()->label(__('app.edit'))->icon('heroicon-o-pencil-square'),
            ])
            ->emptyStateActions([
                CreateAction::make()->label(__('m1.add_classification'))->icon('heroicon-o-plus'),
            ])
            ->defaultSort('code')
            ->striped()
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->searchPlaceholder(__('m1.classification_search_hint'))
            ->emptyStateHeading(__('app.no_data'));
    }
}
