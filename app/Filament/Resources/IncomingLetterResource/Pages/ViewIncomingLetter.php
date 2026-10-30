<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\IncomingLetterResource;
use App\Models\IncomingLetter;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomingLetter extends ViewRecord
{
    protected static string $resource = IncomingLetterResource::class;

    protected function getHeaderActions(): array
    {
        /** @var IncomingLetter $record */
        $record = $this->getRecord();

        return $record->disposition && (auth()->user()?->can('print', $record->disposition) ?? false)
            ? [Action::make('printDisposition')->label(__('disposition.print'))->icon('heroicon-o-printer')->url(route('dispositions.print', $record->disposition))->openUrlInNewTab()]
            : [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('incoming.detail'))
                ->columns(2)
                ->schema([
                    TextEntry::make('agenda_number')->label(__('incoming.agenda_number')),
                    TextEntry::make('status')->label(__('incoming.status'))->badge(),
                    TextEntry::make('received_at')->label(__('incoming.received_at'))->date('d M Y'),
                    TextEntry::make('letter_date')->label(__('incoming.letter_date'))->date('d M Y'),
                    TextEntry::make('letter_number')->label(__('incoming.letter_number'))->placeholder('—'),
                    TextEntry::make('sender_name')->label(__('incoming.sender_name')),
                    TextEntry::make('subject')->label(__('incoming.subject'))->columnSpanFull(),
                    TextEntry::make('classification.name')->label(__('incoming.classification'))->placeholder('—'),
                    TextEntry::make('note')->label(__('incoming.note'))->columnSpanFull()->placeholder('—'),
                    TextEntry::make('registrar.name')->label(__('incoming.registered_by')),
                    RepeatableEntry::make('attachments')
                        ->label(__('incoming.attachments'))
                        ->schema([
                            TextEntry::make('original_name')
                                ->label(__('incoming.scans'))
                                ->url(fn ($record): string => route('incoming-letters.attachments.show', $record))
                                ->openUrlInNewTab(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
