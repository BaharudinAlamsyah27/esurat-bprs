<?php

namespace App\Filament\Resources;

use App\Actions\CompleteDispositionRecipient;
use App\Actions\MarkDispositionRead;
use App\Enums\DispositionRecipientStatus;
use App\Filament\Resources\MyDispositionResource\Pages\ListMyDispositions;
use App\Filament\Resources\MyDispositionResource\Pages\ViewMyDisposition;
use App\Models\DispositionRecipient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class MyDispositionResource extends Resource
{
    protected static ?string $model = DispositionRecipient::class;

    protected static ?string $slug = 'disposisi-saya';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'SURAT';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('disposition.my_dispositions');
    }

    public static function getModelLabel(): string
    {
        return __('disposition.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('disposition.my_dispositions');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', [DispositionRecipientStatus::New, DispositionRecipientStatus::Pending])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * @return Builder<DispositionRecipient>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['disposition.incomingLetter.attachments', 'disposition.creator', 'user'])
            ->latest('id');

        if ($user && ! $user->isAdmin()) {
            $query->where('user_id', $user->getKey());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Surat Masuk')
                ->schema([
                    Placeholder::make('letter_number')
                        ->label(__('incoming.letter_number'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->incomingLetter->letter_number ?? '—'),
                    Placeholder::make('sender_name')
                        ->label(__('incoming.sender_name'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->incomingLetter->sender_name ?? '—'),
                    Placeholder::make('subject')
                        ->label(__('incoming.subject'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->incomingLetter->subject ?? '—'),
                    Placeholder::make('letter_date')
                        ->label(__('incoming.letter_date'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->incomingLetter?->letter_date?->translatedFormat('d F Y') ?? '—'),
                    Placeholder::make('attachment')
                        ->label('Berkas Surat')
                        ->content(function (?DispositionRecipient $record): HtmlString {
                            $letter = $record?->disposition?->incomingLetter;
                            $att = $letter?->attachments?->first();

                            if (! $att) {
                                return new HtmlString('<span class="text-gray-400">Tidak ada berkas</span>');
                            }

                            $url = route('incoming-letters.attachments.show', $att);

                            return new HtmlString('<a href="'.$url.'" target="_blank" class="inline-flex items-center gap-1 text-primary-600 underline font-medium hover:text-primary-800">Buka Berkas Surat</a>');
                        }),
                ])->columns(2),

            Section::make('Lembar Disposisi Pimpinan')
                ->schema([
                    Placeholder::make('nature')
                        ->label(__('disposition.nature'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->nature?->label() ?? '—'),
                    Placeholder::make('disposition_date')
                        ->label(__('disposition.date'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition?->disposition_date?->translatedFormat('d F Y') ?? '—'),
                    Placeholder::make('instructions')
                        ->label(__('disposition.instructions_label'))
                        ->content(function (?DispositionRecipient $record): HtmlString {
                            $labels = $record?->disposition?->instructionLabels() ?? [];
                            if (empty($labels)) {
                                return new HtmlString('—');
                            }
                            $items = implode('</li><li>', array_map('e', $labels));

                            return new HtmlString('<ul class="list-disc list-inside"><li>'.$items.'</li></ul>');
                        }),
                    Placeholder::make('note')
                        ->label(__('disposition.note'))
                        ->content(fn (?DispositionRecipient $record): string => $record?->disposition->note ?? '—'),
                ])->columns(2),

            Section::make('Status Tindak Lanjut Anda')
                ->schema([
                    Placeholder::make('recipient_status')
                        ->label('Status')
                        ->content(fn (?DispositionRecipient $record): string => $record?->status?->label() ?? '—'),
                    Placeholder::make('done_at')
                        ->label('Waktu Selesai')
                        ->content(fn (?DispositionRecipient $record): string => $record?->done_at?->translatedFormat('d F Y H:i') ?? '—'),
                    Placeholder::make('done_note')
                        ->label('Catatan Tindak Lanjut')
                        ->content(fn (?DispositionRecipient $record): string => $record->done_note ?? '—')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('disposition.incomingLetter.subject')
                    ->label(__('incoming.subject'))
                    ->limit(40)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('disposition.incomingLetter.sender_name')
                    ->label(__('incoming.sender_name'))
                    ->limit(25)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('disposition.nature')
                    ->label(__('disposition.nature'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('disposition.disposition_date')
                    ->label(__('disposition.date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DispositionRecipientStatus $state): string => $state->color())
                    ->formatStateUsing(fn (DispositionRecipientStatus $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DispositionRecipientStatus::options()),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat')
                    ->before(function (DispositionRecipient $record): void {
                        $user = auth()->user();
                        if ($user) {
                            app(MarkDispositionRead::class)->execute($user, $record);
                        }
                    }),
                Action::make('mark_done')
                    ->label(__('disposition.action_mark_done'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DispositionRecipient $record): bool => ! $record->status->isCompleted())
                    ->modalHeading(__('disposition.mark_done_heading'))
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Textarea::make('done_note')
                            ->label(__('disposition.done_note_label'))
                            ->placeholder(__('disposition.done_note_placeholder'))
                            ->maxLength(255)
                            ->rows(3),
                    ])
                    ->action(function (DispositionRecipient $record, array $data): void {
                        $user = auth()->user();
                        if ($user) {
                            app(CompleteDispositionRecipient::class)->execute($user, $record, $data['done_note'] ?? null);
                            Notification::make()
                                ->title('Disposisi ditandai selesai')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('print')
                    ->label(__('disposition.print'))
                    ->icon('heroicon-o-printer')
                    ->url(fn (DispositionRecipient $record): string => route('dispositions.print', $record->disposition_id))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyDispositions::route('/'),
            'view' => ViewMyDisposition::route('/{record}'),
        ];
    }
}
