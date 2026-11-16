<?php

namespace App\Http\Controllers\Api;

use App\Enums\DispositionRecipientStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\IncomingLetterResource;
use App\Models\CooperationAgreement;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $recentIncoming = IncomingLetter::query()
            ->with(['classification', 'attachments', 'disposition'])
            ->latest('letter_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $unreadDispositions = DispositionRecipient::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [DispositionRecipientStatus::New, DispositionRecipientStatus::Pending])
            ->count();

        $dispositionsTotal = DispositionRecipient::query()
            ->where('user_id', $user->getKey())
            ->count();

        return response()->json([
            'data' => [
                'incoming_total' => IncomingLetter::query()->count(),
                'outgoing_total' => OutgoingLetter::query()->count(),
                'cooperation_total' => CooperationAgreement::query()->count(),
                'unread_dispositions' => $unreadDispositions,
                'dispositions_total' => $dispositionsTotal,
                'recent_incoming' => IncomingLetterResource::collection($recentIncoming)->resolve($request),
            ],
        ]);
    }
}
