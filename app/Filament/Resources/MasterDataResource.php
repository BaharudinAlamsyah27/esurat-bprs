<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
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
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('m1.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('m1.name'))->searchable()->sortable(),
                TextColumn::make('description')->label(__('m1.description'))->limit(60)->toggleable(),
            ])
            ->actions([
                EditAction::make()->label(__('app.edit')),
                DeleteAction::make()->label(__('app.delete'))->requiresConfirmation(),
            ])
            ->paginated([10, 25]);
    }
}
