<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\IncomingLetterResource;
use App\Models\IncomingLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class IncomingLetterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', IncomingLetter::class);

        $query = IncomingLetter::query()
            ->with(['classification', 'attachments', 'disposition.recipients'])
            ->latest('received_at')
            ->latest('id');

        // Pencarian sesuai PRD: nomor surat, pengirim, perihal.
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('classification_id')) {
            $query->where('classification_id', $request->integer('classification_id'));
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return IncomingLetterResource::collection($query->paginate($perPage));
    }

    public function show(IncomingLetter $incomingLetter): IncomingLetterResource
    {
        Gate::authorize('view', $incomingLetter);

        $incomingLetter->load(['classification', 'attachments', 'disposition.recipients.user']);

        return new IncomingLetterResource($incomingLetter);
    }
}
