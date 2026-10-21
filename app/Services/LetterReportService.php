<?php

namespace App\Services;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LetterReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<IncomingLetter>|Builder<OutgoingLetter>
     */
    public function query(string $type, array $filters): Builder
    {
        return match ($type) {
            'incoming' => $this->incomingQuery($filters),
            'outgoing' => $this->outgoingQuery($filters),
            default => abort(404),
        };
    }

    /** @param array<string, mixed> $filters
     * @return Builder<IncomingLetter>
     */
    public function incomingQuery(array $filters): Builder
    {
        return $this->filterQuery(IncomingLetter::query()->with('classification'), $this->normalizeFilters('incoming', $filters));
    }

    /** @param array<string, mixed> $filters
     * @return Builder<OutgoingLetter>
     */
    public function outgoingQuery(array $filters): Builder
    {
        return $this->filterQuery(OutgoingLetter::query()->with('classification'), $this->normalizeFilters('outgoing', $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start_date: string, end_date: string, date_field: string, classification_id: int|string|null}
     */
    public function validatedFilters(string $type, array $filters): array
    {
        return $this->normalizeFilters($type, $filters);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{start_date: string, end_date: string, date_field: string, classification_id: int|string|null}  $filters
     * @return Builder<TModel>
     */
    private function filterQuery(Builder $query, array $filters): Builder
    {
        $query->whereDate($filters['date_field'], '>=', $filters['start_date'])
            ->whereDate($filters['date_field'], '<=', $filters['end_date']);

        if (filled($filters['classification_id'])) {
            $query->where('classification_id', $filters['classification_id']);
        }

        return $query->orderBy($filters['date_field'])->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start_date: string, end_date: string, date_field: string, classification_id: int|string|null}
     */
    private function normalizeFilters(string $type, array $filters): array
    {
        if (! in_array($type, ['incoming', 'outgoing'], true)) {
            abort(404);
        }

        $validated = validator($filters, [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'date_field' => ['required', 'in:letter_date,received_at,sent_date'],
            'classification_id' => ['nullable', 'integer', 'exists:classifications,id'],
        ])->validate();

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();

        if ($start->diffInDays($end) > 365) {
            throw ValidationException::withMessages([
                'end_date' => __('reports.date_range_too_long'),
            ]);
        }

        $dateField = (string) $validated['date_field'];
        if (($type === 'incoming' && $dateField === 'sent_date') || ($type === 'outgoing' && $dateField === 'received_at')) {
            $dateField = 'letter_date';
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'date_field' => $dateField,
            'classification_id' => filled($validated['classification_id'] ?? null) ? (string) $validated['classification_id'] : null,
        ];
    }
}
