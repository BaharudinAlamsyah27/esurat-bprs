<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Actions\ArchiveIncomingLetter;
use App\Actions\ForwardIncomingLetter;
use App\Filament\Resources\IncomingLetterResource;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomingLetter extends ViewRecord
{
    protected static string $resource = IncomingLetterResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('incoming.detail'))
                ->columns(2)
                ->schema([
                    TextEntry::make('agenda_number')->label(__('incoming.agenda_number')),
                    TextEntry::make('status')
                        ->label(__('incoming.status'))
                        ->badge()
                        ->formatStateUsing(fn (IncomingLetter $record): string => $record->status->getLabel())
                        ->color(fn (IncomingLetter $record): string => $record->status->getColor()),
                    TextEntry::make('received_at')->label(__('incoming.received_at'))->date('d M Y'),
                    TextEntry::make('letter_date')->label(__('incoming.letter_date'))->date('d M Y'),
                    TextEntry::make('subject')
                        ->label(__('incoming.subject'))
                        ->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_subject') : $state),
                    TextEntry::make('letter_number')->label(__('incoming.letter_number'))->placeholder('—'),
                    TextEntry::make('sender_name')->label(__('incoming.sender_name'))->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_sender') : $state)->visible($this->canViewContents(...)),
                    TextEntry::make('sender_institution')->label(__('incoming.sender_institution'))->visible($this->canViewContents(...)),
                    TextEntry::make('initialRecipient.name')->label(__('incoming.initial_recipient'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('letterType.name')->label(__('incoming.letter_type'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('classification.name')->label(__('incoming.classification'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('urgency')->label(__('incoming.urgency'))->badge()->visible($this->canViewContents(...)),
                    TextEntry::make('confidentiality')->label(__('incoming.confidentiality'))->badge()->visible($this->canViewContents(...)),
                    TextEntry::make('delivery_method')->label(__('incoming.delivery_method'))->visible($this->canViewContents(...)),
                    TextEntry::make('summary')->label(__('incoming.summary'))->columnSpanFull()->visible($this->canViewContents(...)),
                    TextEntry::make('registered_by')->label(__('incoming.registered_by'))->formatStateUsing(fn (IncomingLetter $record): string => $record->registrar->name)->visible($this->canViewContents(...)),
                    RepeatableEntry::make('attachments')
                        ->label(__('incoming.attachments'))
                        ->schema([
                            TextEntry::make('original_name')
                                ->label(__('incoming.scans'))
                                ->url(fn ($record): string => route('incoming-letters.attachments.show', $record))
                                ->openUrlInNewTab(),
                        ])
                        ->columnSpanFull()
                        ->visible(fn (IncomingLetter $record): bool => auth()->user()?->can('viewAttachments', $record) ?? false),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('incoming.print'))
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('incoming-letters.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('viewContents', $this->getRecord()) ?? false),
            Action::make('forward')
                ->label(__('incoming.forward'))
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription(__('incoming.forward_confirmation'))
                ->visible(fn (): bool => auth()->user()?->can('forward', $this->getRecord()) ?? false)
                ->action(function (): void {
                    /** @var User $user */
                    $user = auth()->user();
                    app(ForwardIncomingLetter::class)->execute($user, $this->currentIncomingLetter());
                    $this->currentIncomingLetter()->refresh();
                    Notification::make()->title(__('incoming.forwarded'))->success()->send();
                }),
            Action::make('archive')
                ->label(__('incoming.archive'))
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('incoming.archive_confirmation'))
                ->form([
                    TextInput::make('archive_location')->label(__('incoming.archive_location'))->maxLength(255),
                ])
                ->visible(fn (): bool => auth()->user()?->can('archive', $this->getRecord()) ?? false)
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = auth()->user();
                    app(ArchiveIncomingLetter::class)->execute($user, $this->currentIncomingLetter(), $data['archive_location'] ?? null);
                    $this->currentIncomingLetter()->refresh();
                    Notification::make()->title(__('incoming.archived'))->success()->send();
                }),
        ];
    }

    private function canViewContents(IncomingLetter $record): bool
    {
        return auth()->user()?->can('viewContents', $record) ?? false;
    }

    private function currentIncomingLetter(): IncomingLetter
    {
        $record = $this->getRecord();

        if (! $record instanceof IncomingLetter) {
            abort(404);
        }

        return $record;
    }
}
