<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingLetterResource\Pages\CreateIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\EditIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\ListIncomingLetters;
use App\Filament\Resources\IncomingLetterResource\Pages\ViewIncomingLetter;
use App\Models\IncomingLetter;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncomingLetterResource extends Resource
{
    protected static ?string $model = IncomingLetter::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'SURAT';

    public static function getNavigationLabel(): string
    {
        return __('incoming.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('incoming.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('incoming.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', IncomingLetter::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('incoming.singular'))
                ->columns(2)
                ->schema([
                    TextInput::make('letter_number')->label(__('incoming.letter_number'))->required()->maxLength(120),
                    TextInput::make('sender_name')->label(__('incoming.sender_name'))->required()->maxLength(255),
                    TextInput::make('subject')->label(__('incoming.subject'))->required()->maxLength(255)->columnSpanFull(),
                    DatePicker::make('letter_date')->label(__('incoming.letter_date'))->required(),
                    DatePicker::make('received_at')->label(__('incoming.received_at'))->default(today())->required(),
                    Select::make('classification_id')
                        ->label(__('incoming.classification'))
                        ->relationship('classification', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    FileUpload::make('scan')
                        ->label(__('incoming.scan'))
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText(__('incoming.scan_help'))
                        ->columnSpanFull()
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                    Checkbox::make('confirm_duplicate')
                        ->label(__('incoming.confirm_duplicate'))
                        ->visible(fn (Get $get): bool => self::hasDuplicate($get)),
                ]),
            Section::make(__('incoming.additional_info'))
                ->collapsed()
                ->schema([
                    Textarea::make('note')->label(__('incoming.note'))->rows(3)->maxLength(2000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder(__('incoming.search_hint'))
            ->columns([
                TextColumn::make('agenda_number')->label(__('incoming.agenda_number'))->sortable(),
                TextColumn::make('letter_number')->label(__('incoming.letter_number'))->searchable(),
                TextColumn::make('sender_name')->label(__('incoming.sender_name'))->searchable(),
                TextColumn::make('subject')->label(__('incoming.subject'))->searchable()->limit(45),
                TextColumn::make('letter_date')->label(__('incoming.letter_date'))->date('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('agenda_year')
                    ->label(__('incoming.year'))
                    ->options(fn (): array => IncomingLetter::query()->select('agenda_year')->distinct()->orderByDesc('agenda_year')->pluck('agenda_year', 'agenda_year')->all()),
                SelectFilter::make('classification_id')->label(__('incoming.classification'))->relationship('classification', 'name'),
            ])
            ->actions([
                ViewAction::make()->label(__('incoming.detail')),
                EditAction::make()->label(__('app.edit')),
                DeleteAction::make()->label(__('app.delete'))->requiresConfirmation(),
            ])
            ->defaultSort('received_at', 'desc')
            ->paginated([10, 25])
            ->emptyStateHeading(__('incoming.empty'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomingLetters::route('/'),
            'create' => CreateIncomingLetter::route('/create'),
            'view' => ViewIncomingLetter::route('/{record}'),
            'edit' => EditIncomingLetter::route('/{record}/edit'),
        ];
    }

    private static function hasDuplicate(Get $get): bool
    {
        $letterNumber = trim((string) $get('letter_number'));
        $senderName = trim((string) $get('sender_name'));
        $letterDate = $get('letter_date');

        return $letterNumber !== '' && $senderName !== '' && filled($letterDate)
            && IncomingLetter::query()
                ->where('letter_number', $letterNumber)
                ->where('sender_name', $senderName)
                ->whereDate('letter_date', $letterDate)
                ->exists();
    }
}
