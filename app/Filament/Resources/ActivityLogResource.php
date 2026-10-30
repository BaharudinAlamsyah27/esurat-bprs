<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'DATA MASTER';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('m1.activity_log');
    }

    public static function getModelLabel(): string
    {
        return __('m1.activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.activity_log');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('m1.activity_time'))->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('causer.name')->label(__('m1.activity_user'))->placeholder(__('m1.system_user')),
                TextColumn::make('description')->label(__('m1.activity_description'))->searchable()->wrap()->placeholder('—'),
                TextColumn::make('subject_type')
                    ->label(__('m1.activity_object'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('app.no_data'))
            ->searchPlaceholder(__('m1.activity_search_hint'));
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
