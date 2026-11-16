<?php

namespace App\Filament\Resources\CooperationAgreementResource\Pages;

use App\Filament\Resources\CooperationAgreementResource;
use App\Models\Attachment;
use App\Models\CooperationAgreement;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCooperationAgreement extends ViewRecord
{
    protected static string $resource = CooperationAgreementResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('cooperation.detail'))
                ->columns(2)
                ->schema([
                    TextEntry::make('bss_pks_number')->label(__('cooperation.bss_pks_number')),
                    TextEntry::make('partner_pks_number')->label(__('cooperation.partner_pks_number'))->placeholder('—'),
                    TextEntry::make('partner_name')->label(__('cooperation.partner_name')),
                    TextEntry::make('partner_npwp')->label(__('cooperation.partner_npwp'))->placeholder('—'),
                    TextEntry::make('agreement_date')->label(__('cooperation.agreement_date'))->date('d M Y'),
                    TextEntry::make('pic_name')->label(__('cooperation.pic_name')),
                    TextEntry::make('subject')->label(__('cooperation.subject'))->columnSpanFull(),
                    TextEntry::make('note')->label(__('cooperation.note'))->columnSpanFull()->placeholder('—'),
                    TextEntry::make('creator.name')->label(__('cooperation.created_by')),
                    TextEntry::make('pks_file_link')
                        ->label(__('cooperation.pks_file'))
                        ->state(function (CooperationAgreement $record): string {
                            $attachment = $record->pksAttachment();

                            return $attachment instanceof Attachment ? $attachment->original_name : __('cooperation.no_file');
                        })
                        ->url(function (CooperationAgreement $record): ?string {
                            $attachment = $record->pksAttachment();

                            return $attachment instanceof Attachment ? route('cooperation-agreements.attachments.show', $attachment) : null;
                        })
                        ->openUrlInNewTab(),
                    TextEntry::make('legality_file_link')
                        ->label(__('cooperation.partner_legality'))
                        ->state(function (CooperationAgreement $record): string {
                            $attachment = $record->legalityAttachment();

                            return $attachment instanceof Attachment ? $attachment->original_name : __('cooperation.no_file');
                        })
                        ->url(function (CooperationAgreement $record): ?string {
                            $attachment = $record->legalityAttachment();

                            return $attachment instanceof Attachment ? route('cooperation-agreements.attachments.show', $attachment) : null;
                        })
                        ->openUrlInNewTab(),
                ]),
        ]);
    }
}
