<?php

namespace App\Filament\Resources;

use App\Actions\RegisterOutgoingLetter;
use App\Actions\UpdateOutgoingLetter;
use App\Filament\Resources\OutgoingLetterResource\Pages\CreateOutgoingLetter;
use App\Filament\Resources\OutgoingLetterResource\Pages\EditOutgoingLetter;
use App\Filament\Resources\OutgoingLetterResource\Pages\ListOutgoingLetters;
use App\Filament\Resources\OutgoingLetterResource\Pages\ViewOutgoingLetter;
use App\Models\Classification;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterNumberGenerator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class OutgoingLetterResource extends Resource
{
    protected static ?string $model = OutgoingLetter::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'SURAT';

    public static function getNavigationLabel(): string
    {
        return __('outgoing.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('outgoing.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('outgoing.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', OutgoingLetter::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('outgoing.singular'))
                ->columns(2)
                ->schema([
                    TextInput::make('letter_number')
                        ->label(__('outgoing.manual_number'))
                        ->placeholder(__('outgoing.manual_number_placeholder'))
                        ->helperText(__('outgoing.manual_number_help'))
                        ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false))
                        ->visible(fn (string $operation): bool => $operation === 'edit' || (auth()->user()?->isAdmin() ?? false))
                        ->maxLength(120),
                    TextInput::make('letter_number_preview')
                        ->label(__('outgoing.letter_number'))
                        ->default(fn (Get $get): ?string => self::numberPreview($get))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->helperText(__('outgoing.number_preview_help')),
                    TextInput::make('agenda_number')
                        ->label(__('outgoing.agenda_number'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?OutgoingLetter $record): bool => $record !== null),
                    TextInput::make('recipient')->label(__('outgoing.recipient'))->required()->maxLength(255),
                    TextInput::make('subject')->label(__('outgoing.subject'))->required()->maxLength(255)->columnSpanFull(),
                    Select::make('classification_id')
                        ->label(__('outgoing.classification'))
                        ->relationship('classification', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => $set('letter_number_preview', self::numberPreview($get)))
                        ->required(),
                    DatePicker::make('letter_date')
                        ->label(__('outgoing.letter_date'))
                        ->default(today())
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => $set('letter_number_preview', self::numberPreview($get)))
                        ->required(),
                    DatePicker::make('sent_date')->label(__('outgoing.sent_date'))->nullable(),
                    FileUpload::make('scan')
                        ->label(__('outgoing.scan'))
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText(__('outgoing.scan_help'))
                        ->columnSpanFull(),
                ]),
            Section::make(__('outgoing.additional_info'))
                ->collapsed()
                ->schema([Textarea::make('note')->label(__('outgoing.note'))->rows(3)->maxLength(2000)]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('classification'))
            ->description(__('outgoing.table_help'))
            ->searchPlaceholder(__('outgoing.search_hint'))
            ->columns([
                TextColumn::make('agenda_number')->label(__('outgoing.agenda_number'))->sortable(),
                TextColumn::make('letter_number')->label(__('outgoing.letter_number'))->searchable(),
                TextColumn::make('recipient')->label(__('outgoing.recipient'))->searchable(),
                TextColumn::make('subject')->label(__('outgoing.subject'))->searchable()->limit(45),
                TextColumn::make('letter_date')->label(__('outgoing.letter_date'))->date('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('agenda_year')
                    ->label(__('outgoing.year'))
                    ->options(fn (): array => OutgoingLetter::query()->select('agenda_year')->distinct()->orderByDesc('agenda_year')->pluck('agenda_year', 'agenda_year')->all()),
                SelectFilter::make('classification_id')->label(__('outgoing.classification'))->relationship('classification', 'name'),
            ])
            ->actions([
                ViewAction::make()->label(__('outgoing.detail')),
                EditAction::make()->label(__('app.edit')),
                DeleteAction::make()->label(__('app.delete'))->requiresConfirmation(),
            ])
            ->defaultSort('letter_date', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->emptyStateHeading(__('outgoing.empty'));
    }

    /** @param array<string, mixed> $data */
    public static function register(array $data): OutgoingLetter
    {
        $file = $data['scan'] ?? null;
        unset($data['scan']);

        /** @var User $user */
        $user = auth()->user();

        return app(RegisterOutgoingLetter::class)->execute($user, $data, $file instanceof UploadedFile ? $file : null);
    }

    /** @param array<string, mixed> $data */
    public static function update(OutgoingLetter $record, array $data): OutgoingLetter
    {
        $file = $data['scan'] ?? null;
        unset($data['scan']);

        /** @var User $user */
        $user = auth()->user();

        return app(UpdateOutgoingLetter::class)->execute($user, $record, $data, $file instanceof UploadedFile ? $file : null);
    }

    private static function numberPreview(Get $get): ?string
    {
        $classificationId = $get('classification_id');
        $letterDate = $get('letter_date');
        if (blank($classificationId) || blank($letterDate)) {
            return null;
        }

        $classificationCode = Classification::query()->whereKey($classificationId)->value('code');
        if (! is_string($classificationCode)) {
            return null;
        }

        $date = Carbon::parse($letterDate);

        return app(LetterNumberGenerator::class)->preview((int) $date->year, (int) $date->month, $classificationCode);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutgoingLetters::route('/'),
            'create' => CreateOutgoingLetter::route('/create'),
            'view' => ViewOutgoingLetter::route('/{record}'),
            'edit' => EditOutgoingLetter::route('/{record}/edit'),
        ];
    }
}
