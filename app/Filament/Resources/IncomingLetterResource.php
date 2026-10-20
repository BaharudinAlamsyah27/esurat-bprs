<?php

namespace App\Filament\Resources;

use App\Enums\IncomingDeliveryMethod;
use App\Enums\IncomingLetterStatus;
use App\Enums\IncomingLetterUrgency;
use App\Enums\LetterConfidentiality;
use App\Filament\Resources\IncomingLetterResource\Pages\CreateIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\EditIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\ListIncomingLetters;
use App\Filament\Resources\IncomingLetterResource\Pages\ViewIncomingLetter;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    DatePicker::make('received_at')
                        ->label(__('incoming.received_at'))
                        ->default(today())
                        ->required()
                        ->live(),
                    DatePicker::make('letter_date')
                        ->label(__('incoming.letter_date'))
                        ->required()
                        ->live(),
                    TextInput::make('letter_number')
                        ->label(__('incoming.letter_number'))
                        ->maxLength(120)
                        ->live(debounce: 500),
                    TextInput::make('sender_name')
                        ->label(__('incoming.sender_name'))
                        ->required()
                        ->maxLength(255)
                        ->live(debounce: 500),
                    TextInput::make('sender_institution')
                        ->label(__('incoming.sender_institution'))
                        ->maxLength(255),
                    TextInput::make('subject')
                        ->label(__('incoming.subject'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('initial_recipient_id')
                        ->label(__('incoming.initial_recipient'))
                        ->relationship(
                            name: 'initialRecipient',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('is_active', true)
                                ->whereHas('roles', fn (Builder $roles): Builder => $roles->whereIn('name', ['direksi', 'kepala_unit'])),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    FileUpload::make('scans')
                        ->label(__('incoming.scans'))
                        ->multiple()
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText(__('incoming.scan_help'))
                        ->columnSpanFull()
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                    Toggle::make('confirm_duplicate')
                        ->label(__('incoming.confirm_duplicate'))
                        ->helperText(__('incoming.duplicate_warning'))
                        ->visible(fn (Get $get): bool => self::hasDuplicate($get)),
                ]),
            Section::make(__('incoming.additional_info'))
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('urgency')
                        ->label(__('incoming.urgency'))
                        ->options(collect(IncomingLetterUrgency::cases())->mapWithKeys(fn (IncomingLetterUrgency $value): array => [$value->value => $value->getLabel()])->all())
                        ->default(IncomingLetterUrgency::Normal->value)
                        ->required(),
                    Select::make('confidentiality')
                        ->label(__('incoming.confidentiality'))
                        ->options(collect(LetterConfidentiality::cases())->mapWithKeys(fn (LetterConfidentiality $value): array => [$value->value => $value->getLabel()])->all())
                        ->default(LetterConfidentiality::Ordinary->value)
                        ->required(),
                    Select::make('delivery_method')
                        ->label(__('incoming.delivery_method'))
                        ->options(collect(IncomingDeliveryMethod::cases())->mapWithKeys(fn (IncomingDeliveryMethod $value): array => [$value->value => $value->getLabel()])->all())
                        ->default(IncomingDeliveryMethod::Direct->value)
                        ->required(),
                    Select::make('letter_type_id')
                        ->label(__('incoming.letter_type'))
                        ->relationship('letterType', 'name', fn (Builder $query): Builder => $query->where('is_active', true))
                        ->searchable()
                        ->preload(),
                    Select::make('classification_id')
                        ->label(__('incoming.classification'))
                        ->relationship('classification', 'name', fn (Builder $query): Builder => $query->where('is_active', true))
                        ->searchable()
                        ->preload(),
                    Textarea::make('summary')
                        ->label(__('incoming.summary'))
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agenda_number')->label(__('incoming.agenda_number'))->searchable()->sortable(),
                TextColumn::make('received_at')->label(__('incoming.received_at'))->date('d M Y')->sortable(),
                TextColumn::make('sender_name')
                    ->label(__('incoming.sender_name'))
                    ->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_sender') : $state)
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchVisibleContent($query, $search, 'sender_name')),
                TextColumn::make('subject')
                    ->label(__('incoming.subject'))
                    ->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_subject') : $state)
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchVisibleContent($query, $search, 'subject')),
                TextColumn::make('status')
                    ->label(__('incoming.status'))
                    ->badge()
                    ->formatStateUsing(fn (IncomingLetterStatus $state): string => $state->getLabel())
                    ->color(fn (IncomingLetterStatus $state): string => $state->getColor()),
            ])
            ->filters([
                SelectFilter::make('agenda_year')
                    ->label(__('incoming.year'))
                    ->options(fn (): array => IncomingLetter::query()
                        ->select('agenda_year')
                        ->distinct()
                        ->orderByDesc('agenda_year')
                        ->pluck('agenda_year', 'agenda_year')
                        ->all()),
                SelectFilter::make('status')
                    ->label(__('incoming.status'))
                    ->options(collect(IncomingLetterStatus::cases())->mapWithKeys(fn (IncomingLetterStatus $value): array => [$value->value => $value->getLabel()])->all()),
                SelectFilter::make('urgency')
                    ->label(__('incoming.urgency'))
                    ->options(collect(IncomingLetterUrgency::cases())->mapWithKeys(fn (IncomingLetterUrgency $value): array => [$value->value => $value->getLabel()])->all()),
                SelectFilter::make('confidentiality')
                    ->label(__('incoming.confidentiality'))
                    ->options(collect(LetterConfidentiality::cases())->mapWithKeys(fn (LetterConfidentiality $value): array => [$value->value => $value->getLabel()])->all()),
                SelectFilter::make('letter_type_id')
                    ->label(__('incoming.letter_type'))
                    ->relationship('letterType', 'name'),
                SelectFilter::make('classification_id')
                    ->label(__('incoming.classification'))
                    ->relationship('classification', 'name'),
            ])
            ->actions([
                ViewAction::make()->label(__('incoming.detail')),
                EditAction::make()->label(__('app.edit')),
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

        if ($letterNumber === '' || $senderName === '' || blank($letterDate)) {
            return false;
        }

        return IncomingLetter::query()
            ->where('letter_number', $letterNumber)
            ->where('sender_name', $senderName)
            ->whereDate('letter_date', $letterDate)
            ->exists();
    }

    /** @param Builder<IncomingLetter> $query
     * @return Builder<IncomingLetter>
     */
    private static function searchVisibleContent(Builder $query, string $search, string $column): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($column, 'like', '%'.$search.'%')
            ->where(function (Builder $visible) use ($user): void {
                $visible->where('confidentiality', LetterConfidentiality::Ordinary->value);

                if ($user->hasRole('direksi')) {
                    $visible->orWhereIn('confidentiality', [
                        LetterConfidentiality::Restricted->value,
                        LetterConfidentiality::Secret->value,
                    ]);

                    return;
                }

                $visible->orWhere('registered_by', $user->getKey())
                    ->orWhere('initial_recipient_id', $user->getKey());

                if ($user->hasRole('kepala_unit') && $user->unit_id !== null) {
                    $visible->orWhereHas('initialRecipient', fn (Builder $recipient): Builder => $recipient->where('unit_id', $user->unit_id));
                }
            });

        return $query;
    }
}
