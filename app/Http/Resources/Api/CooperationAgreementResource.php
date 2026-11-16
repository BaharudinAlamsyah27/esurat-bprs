<?php

namespace App\Http\Resources\Api;

use App\Models\Attachment;
use App\Models\CooperationAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CooperationAgreementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CooperationAgreement $agreement */
        $agreement = $this->resource;

        $pksFile = $agreement->attachments->firstWhere('kind', 'pks_file');
        $legalityFile = $agreement->attachments->firstWhere('kind', 'partner_legality');

        return [
            'id' => $agreement->id,
            'bss_pks_number' => $agreement->bss_pks_number,
            'partner_pks_number' => $agreement->partner_pks_number,
            'partner_npwp' => $agreement->partner_npwp,
            'agreement_date' => $agreement->agreement_date->format('Y-m-d'),
            'partner_name' => $agreement->partner_name,
            'subject' => $agreement->subject,
            'pic_name' => $agreement->pic_name,
            'note' => $agreement->note,
            'pks_file_url' => $pksFile instanceof Attachment
                ? route('api.attachments.show', $pksFile)
                : null,
            'partner_legality_url' => $legalityFile instanceof Attachment
                ? route('api.attachments.show', $legalityFile)
                : null,
            'created_at' => $agreement->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
