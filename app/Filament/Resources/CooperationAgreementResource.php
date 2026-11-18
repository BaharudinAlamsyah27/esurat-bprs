<?php

namespace App\Filament\Resources;

use App\Actions\SaveCooperationAgreement;
use App\Filament\Resources\CooperationAgreementResource\Pages\CreateCooperationAgreement;
use App\Filament\Resources\CooperationAgreementResource\Pages\EditCooperationAgreement;
use App\Filament\Resources\CooperationAgreementResource\Pages\ListCooperationAgreements;
use App\Filament\Resources\CooperationAgreementResource\Pages\ViewCooperationAgreement;
use App\Models\Attachment;
use App\Models\CooperationAgreement;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CooperationAgreementResource extends Resource
{
    protected static ?string $model = CooperationAgreement::class;

    protected static ?string $slug = 'perjanjian-kerjasama';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'ARSIP';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('cooperation.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('cooperation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cooperation.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', CooperationAgreement::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('cooperation.section_main'))
                ->columns(2)
                ->schema([
                    TextInput::make('bss_pks_number')
                        ->label(__('cooperation.bss_pks_number'))
                        ->placeholder(__('cooperation.bss_pks_number_placeholder'))
                        ->required()
                        ->maxLength(120),
                    TextInput::make('partner_pks_number')
                        ->label(__('cooperation.partner_pks_number'))
                        ->placeholder(__('cooperation.partner_pks_number_placeholder'))
                        ->nullable()
                        ->maxLength(120),
                    TextInput::make('partner_name')
                        ->label(__('cooperation.partner_name'))
                        ->placeholder(__('cooperation.partner_name_placeholder'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('partner_npwp')
                        ->label(__('cooperation.partner_npwp'))
                        ->placeholder(__('cooperation.partner_npwp_placeholder'))
                        ->nullable()
                        ->maxLength(50),
                    DatePicker::make('agreement_date')
                        ->label(__('cooperation.agreement_date'))
                        ->default(today())
                        ->required(),
                    TextInput::make('pic_name')
                        ->label(__('cooperation.pic_name'))
                        ->placeholder(__('cooperation.pic_name_placeholder'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('subject')
                        ->label(__('cooperation.subject'))
                        ->placeholder(__('cooperation.subject_placeholder'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make(__('cooperation.section_files'))
                ->columns(2)
                ->schema([
                    Placeholder::make('current_pks_file')
                        ->label(__('cooperation.pks_file_current'))
                        ->visible(fn (?CooperationAgreement $record): bool => $record !== null)
                        ->content(fn (?CooperationAgreement $record): HtmlString => self::attachmentLink($record?->pksAttachment())),
                    Placeholder::make('current_legality_file')
                        ->label(__('cooperation.partner_legality_current'))
                        ->visible(fn (?CooperationAgreement $record): bool => $record !== null)
                        ->content(fn (?CooperationAgreement $record): HtmlString => self::attachmentLink($record?->legalityAttachment())),
                    FileUpload::make('pks_file')
                        ->label(__('cooperation.pks_file'))
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText(__('cooperation.pks_file_help')),
                    FileUpload::make('partner_legality')
                        ->label(__('cooperation.partner_legality'))
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText(__('cooperation.partner_legality_help')),
                ]),

            Section::make(__('cooperation.additional_info'))
                ->collapsed()
                ->schema([
                    Textarea::make('note')
                        ->label(__('cooperation.note'))
                        ->rows(3)
                        ->maxLength(2000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder(__('cooperation.search_hint'))
            ->columns([
                TextColumn::make('bss_pks_number')
                    ->label(__('cooperation.bss_pks_number'))
                    ->width('11rem')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('partner_name')
                    ->label(__('cooperation.partner_name'))
                    ->width('12rem')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('subject')
                    ->label(__('cooperation.subject'))
                    ->width('14rem')
                    ->searchable()
                    ->limit(45)
                    ->placeholder('—'),
                TextColumn::make('pic_name')
                    ->label(__('cooperation.pic_name'))
                    ->width('10rem')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('agreement_date')
                    ->label(__('cooperation.agreement_date'))
                    ->width('8rem')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('agreement_year')
                    ->label(__('cooperation.year'))
                    ->options(function (): array {
                        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
                            ? "strftime('%Y', agreement_date)"
                            : 'YEAR(agreement_date)';

                        $years = CooperationAgreement::query()
                            ->selectRaw("{$yearExpression} as year")
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year')
                            ->filter()
                            ->all();

                        $options = [];
                        foreach ($years as $year) {
                            $options[(string) $year] = (string) $year;
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereYear('agreement_date', (int) $value);
                    }),
            ])
            ->filtersTriggerAction(fn (Action $action, Table $table): Action => $action->extraAttributes([
                'data-active-filters' => $table->getActiveFiltersCount(),
            ]))
            ->actions([
                ViewAction::make()->label(__('app.view'))->icon('heroicon-o-eye'),
                EditAction::make()->label(__('app.edit'))->icon('heroicon-o-pencil-square'),
            ])
            ->defaultSort('agreement_date', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateActions([
                CreateAction::make()->label(__('cooperation.new'))->icon('heroicon-o-plus'),
            ])
            ->emptyStateHeading(__('app.no_data'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCooperationAgreements::route('/'),
            'create' => CreateCooperationAgreement::route('/create'),
            'view' => ViewCooperationAgreement::route('/{record}'),
            'edit' => EditCooperationAgreement::route('/{record}/edit'),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveRecord(array $data, ?CooperationAgreement $record = null): CooperationAgreement
    {
        /** @var User $user */
        $user = auth()->user();

        $pksFile = $data['pks_file'] ?? null;
        $legalityFile = $data['partner_legality'] ?? null;
        unset($data['pks_file'], $data['partner_legality']);

        return app(SaveCooperationAgreement::class)->execute(
            user: $user,
            data: $data,
            agreement: $record,
            pksFile: $pksFile instanceof UploadedFile ? $pksFile : null,
            legalityFile: $legalityFile instanceof UploadedFile ? $legalityFile : null,
        );
    }

    private static function attachmentLink(?Attachment $attachment): HtmlString
    {
        if (! $attachment instanceof Attachment) {
            return new HtmlString('<span class="text-gray-400">'.e(__('cooperation.no_file')).'</span>');
        }

        $url = route('cooperation-agreements.attachments.show', $attachment);

        return new HtmlString(
            '<a href="'.e($url).'" target="_blank" class="inline-flex items-center gap-1 text-primary-600 underline font-medium hover:text-primary-800">'
            .e($attachment->original_name).' ('.e(__('cooperation.open_file')).')</a>'
        );
    }
}
