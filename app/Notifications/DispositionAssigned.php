<?php

namespace App\Notifications;

use App\Models\DispositionRecipient;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DispositionAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DispositionRecipient $dispositionRecipient
    ) {}

    /** @return list<string|class-string> */
    public function via(mixed $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array<string, mixed> */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'disposition_assigned',
            'disposition_recipient_id' => $this->dispositionRecipient->id,
            'disposition_id' => $this->dispositionRecipient->disposition_id,
            'title' => 'Disposisi Baru',
            'body' => 'Anda menerima lembar disposisi baru untuk ditindaklanjuti.',
            'url' => '/disposisi-saya/'.$this->dispositionRecipient->id,
        ];
    }

    /**
     * Strict requirement from PRD:
     * title: "E-Surat BPRS", body: "Anda menerima disposisi baru"
     * Dilarang memuat perihal, pengirim, atau nama pegawai.
     *
     * @return array{notification: array{title: string, body: string}, data: array<string, string>}
     */
    public function toFcm(mixed $notifiable): array
    {
        return [
            'notification' => [
                'title' => 'E-Surat BPRS',
                'body' => 'Anda menerima disposisi baru',
            ],
            'data' => [
                'type' => 'disposition',
                'path' => '/disposisi-saya/'.$this->dispositionRecipient->id,
                'id' => (string) $this->dispositionRecipient->id,
            ],
        ];
    }
}
