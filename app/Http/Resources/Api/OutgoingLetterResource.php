<?php

namespace App\Http\Resources\Api;

use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutgoingLetterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OutgoingLetter $letter */
        $letter = $this->resource;

        $attachment = $letter->attachments->first();

        return [
            'id' => $letter->id,
            'agenda_number' => $letter->agenda_number,
            'letter_number' => $letter->letter_number,
            'letter_date' => $letter->letter_date?->format('Y-m-d'),
            'sent_date' => $letter->sent_date?->format('Y-m-d'),
            'subject' => $letter->subject,
            // Kolom aslinya bernama "recipient" (bukan recipient_name).
            'recipient' => $letter->recipient,
            'note' => $letter->note,
            'classification' => [
                'id' => $letter->classification?->id,
                'code' => $letter->classification?->code,
                'name' => $letter->classification?->name,
            ],
            'attachment_url' => $attachment
                ? route('api.attachments.show', $attachment)
                : null,
            'created_at' => $letter->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
