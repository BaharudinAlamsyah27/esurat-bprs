<?php

namespace App\Http\Resources\Api;

use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DispositionRecipient $recipient */
        $recipient = $this->resource;

        $disposition = $recipient->disposition;
        $letter = $disposition->incomingLetter;
        $attachment = $letter?->attachments->first();

        // Nama pembuat lembar disposisi (pimpinan), bukan pengirim surat.
        $senderName = '-';
        $creator = $disposition->creator;

        if ($creator instanceof User) {
            $senderName = $creator->name;
        }

        return [
            'id' => $recipient->id,
            'disposition_id' => $recipient->disposition_id,
            'user_id' => $recipient->user_id,
            'status' => $recipient->status->value,
            'read_at' => $recipient->read_at?->format('Y-m-d H:i:s'),
            'done_at' => $recipient->done_at?->format('Y-m-d H:i:s'),
            'done_note' => $recipient->done_note,
            'disposition' => [
                'id' => $disposition->id,
                'nature' => $disposition->nature->value,
                'nature_label' => $disposition->nature->label(),
                'disposition_date' => $disposition->disposition_date->format('Y-m-d'),
                'instructions' => $disposition->instructionLabels(),
                'note' => $disposition->note,
                'forwarded_to' => $disposition->forwarded_to ?? [],
                'sender' => $senderName,
                'incoming_letter' => [
                    'id' => $letter?->id,
                    'letter_number' => $letter?->letter_number,
                    'subject' => $letter?->subject,
                    'sender_name' => $letter?->sender_name,
                    'attachment_url' => $attachment
                        ? route('api.attachments.show', $attachment)
                        : null,
                ],
            ],
            'created_at' => $recipient->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
