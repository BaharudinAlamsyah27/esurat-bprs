<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOutgoingLetter extends ViewRecord
{
    protected static string $resource = OutgoingLetterResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('outgoing.detail'))
                ->columns(2)
                ->schema([
                    TextEntry::make('agenda_number')->label(__('outgoing.agenda_number')),
                    TextEntry::make('letter_number')->label(__('outgoing.letter_number')),
                    TextEntry::make('recipient')->label(__('outgoing.recipient')),
                    TextEntry::make('classification.name')->label(__('outgoing.classification')),
                    TextEntry::make('letter_date')->label(__('outgoing.letter_date'))->date('d M Y'),
                    TextEntry::make('sent_date')->label(__('outgoing.sent_date'))->date('d M Y')->placeholder('—'),
                    TextEntry::make('subject')->label(__('outgoing.subject'))->columnSpanFull(),
                    TextEntry::make('note')->label(__('outgoing.note'))->columnSpanFull()->placeholder('—'),
                    TextEntry::make('creator.name')->label(__('outgoing.created_by')),
                    RepeatableEntry::make('attachments')
                        ->label(__('outgoing.attachments'))
                        ->schema([
                            TextEntry::make('original_name')
                                ->label(__('outgoing.scan'))
                                ->url(fn ($record): string => route('outgoing-letters.attachments.show', $record))
                                ->openUrlInNewTab(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
