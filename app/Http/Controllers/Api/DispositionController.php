<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DispositionResource;
use App\Models\DispositionRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DispositionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', DispositionRecipient::class);

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $recipients = DispositionRecipient::query()
            ->with(['disposition.creator', 'disposition.incomingLetter.attachments'])
            ->where('user_id', $request->user()->getKey())
            ->latest('id')
            ->paginate($perPage);

        return DispositionResource::collection($recipients);
    }

    public function show(Request $request, DispositionRecipient $disposition): DispositionResource
    {
        Gate::authorize('view', $disposition);

        // Membuka disposisi sekaligus menandainya sudah dibaca penerima.
        $disposition->markAsRead();
        $disposition->load(['disposition.creator', 'disposition.incomingLetter.attachments']);

        return new DispositionResource($disposition);
    }

    public function markAsDone(Request $request, DispositionRecipient $disposition): DispositionResource
    {
        Gate::authorize('update', $disposition);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $disposition->markAsDone($validated['note']);
        $disposition->load(['disposition.creator', 'disposition.incomingLetter.attachments']);

        return new DispositionResource($disposition);
    }
}
