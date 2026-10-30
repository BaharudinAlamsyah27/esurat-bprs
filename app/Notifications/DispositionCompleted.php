<?php

namespace App\Notifications;

use App\Models\DispositionRecipient;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DispositionCompleted extends Notification implements ShouldQueue
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
            'type' => 'disposition_completed',
            'disposition_recipient_id' => $this->dispositionRecipient->id,
            'disposition_id' => $this->dispositionRecipient->disposition_id,
            'title' => 'Disposisi Ditindaklanjuti',
            'body' => ($this->dispositionRecipient->user->name ?? 'Penerima').' telah menandai disposisi selesai ditindaklanjuti.',
            'url' => '/disposisi-saya/'.$this->dispositionRecipient->id,
        ];
    }

    /**
     * @return array{notification: array{title: string, body: string}, data: array<string, string>}
     */
    public function toFcm(mixed $notifiable): array
    {
        return [
            'notification' => [
                'title' => 'E-Surat BPRS',
                'body' => 'Disposisi telah ditindaklanjuti',
            ],
            'data' => [
                'type' => 'disposition_completed',
                'path' => '/disposisi-saya/'.$this->dispositionRecipient->id,
                'id' => (string) $this->dispositionRecipient->id,
            ],
        ];
    }
}
