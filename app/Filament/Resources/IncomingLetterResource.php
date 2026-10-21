<?php

namespace App\Filament\Resources;

use App\Actions\SaveDisposition;
use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Filament\Resources\IncomingLetterResource\Pages\CreateIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\EditIncomingLetter;
use App\Filament\Resources\IncomingLetterResource\Pages\ListIncomingLetters;
use App\Filament\Resources\IncomingLetterResource\Pages\ViewIncomingLetter;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('disposition'))
            ->description(__('incoming.table_help'))
            ->searchPlaceholder(__('incoming.search_hint'))
            ->columns([
                TextColumn::make('agenda_number')->label(__('incoming.agenda_number'))->sortable(),
                TextColumn::make('letter_number')->label(__('incoming.letter_number'))->searchable(),
                TextColumn::make('sender_name')->label(__('incoming.sender_name'))->searchable(),
                TextColumn::make('subject')->label(__('incoming.subject'))->searchable()->limit(45),
                TextColumn::make('letter_date')->label(__('incoming.letter_date'))->date('d M Y')->sortable(),
                TextColumn::make('disposition_status')
                    ->label(__('disposition.filter'))
                    ->state(fn (IncomingLetter $record): string => $record->disposition ? __('disposition.done') : __('disposition.not_done'))
                    ->badge()
                    ->color(fn (IncomingLetter $record): string => $record->disposition ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('agenda_year')
                    ->label(__('incoming.year'))
                    ->options(fn (): array => IncomingLetter::query()->select('agenda_year')->distinct()->orderByDesc('agenda_year')->pluck('agenda_year', 'agenda_year')->all()),
                SelectFilter::make('classification_id')->label(__('incoming.classification'))->relationship('classification', 'name'),
                SelectFilter::make('has_disposition')
                    ->label(__('disposition.filter'))
                    ->options([
                        'yes' => __('disposition.done'),
                        'no' => __('disposition.not_done'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'yes' => $query->has('disposition'),
                        'no' => $query->doesntHave('disposition'),
                        default => $query,
                    }),
            ])
            ->actions([
                Action::make('disposition')
                    ->label(fn (IncomingLetter $record): string => $record->disposition ? __('disposition.edit') : __('disposition.create'))
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn (): bool => auth()->user()?->can('create', Disposition::class) ?? false)
                    ->modalHeading(__('disposition.heading'))
                    ->modalSubmitActionLabel(__('disposition.save_print'))
                    ->modalWidth('2xl')
                    ->form([
                        Radio::make('nature')
                            ->label(__('disposition.nature'))
                            ->options(DispositionNature::options())
                            ->columns(4)
                            ->required(),
                        Textarea::make('forwarded_to_input')
                            ->label(__('disposition.forwarded_to'))
                            ->placeholder(__('disposition.forwarded_to_placeholder'))
                            ->helperText(__('disposition.forwarded_to_help'))
                            ->rows(2)
                            ->required()
                            ->maxLength(2000),
                        CheckboxList::make('instructions')
                            ->label(__('disposition.instructions_label'))
                            ->options(DispositionInstruction::options())
                            ->columns(2)
                            ->live()
                            ->required(),
                        TextInput::make('instruction_other')
                            ->label(__('disposition.instruction_other'))
                            ->placeholder(__('disposition.instruction_other_placeholder'))
                            ->visible(fn (Get $get): bool => in_array(DispositionInstruction::Lainnya->value, (array) $get('instructions'), true))
                            ->required(fn (Get $get): bool => in_array(DispositionInstruction::Lainnya->value, (array) $get('instructions'), true))
                            ->maxLength(255),
                        Textarea::make('note')->label(__('disposition.note'))->rows(3)->maxLength(2000),
                        DatePicker::make('disposition_date')->label(__('disposition.date'))->default(today())->required(),
                    ])
                    ->fillForm(function (IncomingLetter $record): array {
                        $disposition = $record->disposition;

                        if (! $disposition) {
                            return [
                                'nature' => DispositionNature::Biasa->value,
                                'forwarded_to_input' => '',
                                'instructions' => [],
                                'instruction_other' => null,
                                'note' => null,
                                'disposition_date' => today()->toDateString(),
                            ];
                        }

                        return [
                            'nature' => $disposition->nature->value,
                            'forwarded_to_input' => implode("\n", $disposition->forwarded_to ?? []),
                            'instructions' => $disposition->instructions ?? [],
                            'instruction_other' => $disposition->instruction_other,
                            'note' => $disposition->note,
                            'disposition_date' => $disposition->disposition_date->toDateString(),
                        ];
                    })
                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeModalSubmitAction('saveOnly', arguments: ['save_only' => true])
                            ->label(__('disposition.save_only')),
                    ])
                    ->action(function (IncomingLetter $record, array $data, array $arguments): mixed {
                        /** @var User $user */
                        $user = auth()->user();
                        $disposition = app(SaveDisposition::class)->execute($user, $record, $data);

                        if ($arguments['save_only'] ?? false) {
                            Notification::make()->title(__('disposition.saved'))->success()->send();

                            return null;
                        }

                        return redirect()->route('dispositions.print', $disposition);
                    }),
                Action::make('print_disposition')
                    ->label(__('disposition.print'))
                    ->icon('heroicon-o-printer')
                    ->url(fn (IncomingLetter $record): string => $record->disposition
                        ? route('dispositions.print', $record->disposition)
                        : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (IncomingLetter $record): bool => $record->disposition !== null
                        && (auth()->user()?->can('print', $record->disposition) ?? false)),
                ViewAction::make()->label(__('incoming.detail')),
                EditAction::make()->label(__('app.edit')),
                DeleteAction::make()->label(__('app.delete'))->requiresConfirmation(),
            ])
            ->defaultSort('received_at', 'desc')
            ->striped()
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
