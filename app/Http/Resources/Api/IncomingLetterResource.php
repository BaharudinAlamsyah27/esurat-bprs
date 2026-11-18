<?php

namespace App\Http\Resources\Api;

use App\Models\IncomingLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomingLetterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IncomingLetter $letter */
        $letter = $this->resource;

        $attachment = $letter->attachments->first();

        return [
            'id' => $letter->id,
            'agenda_number' => $letter->agenda_number,
            'letter_number' => $letter->letter_number,
            'letter_date' => $letter->letter_date?->format('Y-m-d'),
            'received_at' => $letter->received_at?->format('Y-m-d'),
            'sender_name' => $letter->sender_name,
            'subject' => $letter->subject,
            'note' => $letter->note,
            'status' => $letter->status,
            'classification' => [
                'id' => $letter->classification?->id,
                'code' => $letter->classification?->code,
                'name' => $letter->classification?->name,
            ],
            'attachment_url' => $attachment
                ? route('api.attachments.show', $attachment)
                : null,
            'has_disposition' => $letter->disposition !== null,
            'disposition' => $letter->disposition !== null ? [
                'nature' => $letter->disposition->nature->value,
                'recipient_user_ids' => $letter->disposition->relationLoaded('recipients')
                    ? $letter->disposition->recipients->pluck('user_id')->map(fn ($id): int => (int) $id)->values()->all()
                    : [],
                'instructions' => $letter->disposition->instructions ?? [],
                'instruction_other' => $letter->disposition->instruction_other,
                'note' => $letter->disposition->note,
            ] : null,
            'created_at' => $letter->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
