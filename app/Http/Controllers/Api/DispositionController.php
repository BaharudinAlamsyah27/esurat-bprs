<?php

namespace App\Http\Controllers\Api;

use App\Actions\CompleteDispositionRecipient;
use App\Actions\MarkDispositionRead;
use App\Actions\SaveDisposition;
use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DispositionResource;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    public function show(Request $request, DispositionRecipient $disposition, MarkDispositionRead $markDispositionRead): DispositionResource
    {
        Gate::authorize('view', $disposition);

        // Membuka disposisi sekaligus menandainya sudah dibaca penerima + mencatat audit trail.
        $markDispositionRead->execute($request->user(), $disposition);
        $disposition->load(['disposition.creator', 'disposition.incomingLetter.attachments']);

        return new DispositionResource($disposition);
    }

    public function markAsDone(
        Request $request,
        DispositionRecipient $disposition,
        CompleteDispositionRecipient $completeDispositionRecipient
    ): DispositionResource {
        Gate::authorize('update', $disposition);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ]);

        $completeDispositionRecipient->execute($request->user(), $disposition, $validated['note']);
        $disposition->load(['disposition.creator', 'disposition.incomingLetter.attachments']);

        return new DispositionResource($disposition);
    }

    public function options(): JsonResponse
    {
        Gate::authorize('create', Disposition::class);

        $natures = array_map(
            fn (DispositionNature $nature): array => ['value' => $nature->value, 'label' => $nature->label()],
            DispositionNature::cases(),
        );

        $instructions = array_map(
            fn (DispositionInstruction $instruction): array => ['value' => $instruction->value, 'label' => $instruction->label()],
            DispositionInstruction::cases(),
        );

        $recipients = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'position_title'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'position_title' => $user->position_title,
            ])
            ->values();

        return response()->json([
            'data' => [
                'natures' => $natures,
                'instructions' => $instructions,
                'recipients' => $recipients,
            ],
        ]);
    }

    public function upsertForLetter(
        Request $request,
        IncomingLetter $incomingLetter,
        SaveDisposition $saveDisposition
    ): JsonResponse {
        $disposition = $saveDisposition->execute($request->user(), $incomingLetter, $request->all());

        return response()->json([
            'data' => [
                'id' => $disposition->id,
                'incoming_letter_id' => $disposition->incoming_letter_id,
                'nature' => $disposition->nature->value,
                'disposition_date' => $disposition->disposition_date->format('Y-m-d'),
                'recipient_count' => $disposition->recipients()->count(),
            ],
        ]);
    }
}
