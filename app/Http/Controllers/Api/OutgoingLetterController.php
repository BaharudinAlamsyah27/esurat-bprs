<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OutgoingLetterResource;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OutgoingLetterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', OutgoingLetter::class);

        $query = OutgoingLetter::query()
            ->with(['classification', 'attachments'])
            ->latest('letter_date')
            ->latest('id');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return OutgoingLetterResource::collection($query->paginate($perPage));
    }

    public function show(OutgoingLetter $outgoingLetter): OutgoingLetterResource
    {
        Gate::authorize('view', $outgoingLetter);

        $outgoingLetter->load(['classification', 'attachments']);

        return new OutgoingLetterResource($outgoingLetter);
    }
}
