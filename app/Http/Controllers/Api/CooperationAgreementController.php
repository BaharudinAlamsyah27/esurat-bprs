<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CooperationAgreementResource;
use App\Models\CooperationAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CooperationAgreementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', CooperationAgreement::class);

        $query = CooperationAgreement::query()
            ->with('attachments')
            ->latest('agreement_date')
            ->latest('id');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('bss_pks_number', 'like', "%{$search}%")
                    ->orWhere('partner_pks_number', 'like', "%{$search}%")
                    ->orWhere('partner_npwp', 'like', "%{$search}%")
                    ->orWhere('partner_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('pic_name', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return CooperationAgreementResource::collection($query->paginate($perPage));
    }

    public function show(CooperationAgreement $cooperationAgreement): CooperationAgreementResource
    {
        Gate::authorize('view', $cooperationAgreement);

        $cooperationAgreement->load('attachments');

        return new CooperationAgreementResource($cooperationAgreement);
    }
}
