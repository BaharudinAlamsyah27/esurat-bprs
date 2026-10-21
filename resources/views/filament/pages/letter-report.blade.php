<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-xl font-semibold">{{ __('reports.choose_dates') }}</h2>
            <form wire:submit="generate" class="space-y-5">
                {{ $this->form }}
                <x-filament::button type="submit" size="lg" icon="heroicon-o-magnifying-glass">
                    {{ __('reports.show') }}
                </x-filament::button>
            </form>
        </section>

        @if ($this->hasGenerated)
            @php($rows = $this->getReportRows())
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold">{{ $this->getReportTitle() }}</h2>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $this->getDateRangeLabel() }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.result_count', ['first' => $rows->firstItem() ?? 0, 'last' => $rows->lastItem() ?? 0, 'total' => $rows->total()]) }}</p>
                    </div>
                    @php($filters = http_build_query($this->getReportFilters()))
                    <div class="flex flex-wrap gap-3">
                        <a class="inline-flex min-h-12 items-center rounded-lg border border-gray-300 px-4 font-medium text-gray-800 hover:bg-gray-50" href="{{ route('reports.print', ['type' => $this->getReportType()]).'?'.$filters }}" target="_blank" rel="noopener">{{ __('reports.print') }}</a>
                        <a class="inline-flex min-h-12 items-center rounded-lg bg-primary-600 px-4 font-medium text-white hover:bg-primary-700" href="{{ route('reports.pdf', ['type' => $this->getReportType()]).'?'.$filters }}">{{ __('reports.download_pdf') }}</a>
                        <a class="inline-flex min-h-12 items-center rounded-lg border border-gray-300 px-4 font-medium text-gray-800 hover:bg-gray-50" href="{{ route('reports.excel', ['type' => $this->getReportType()]).'?'.$filters }}">{{ __('reports.download_excel') }}</a>
                    </div>
                </div>
                @if ($rows->isEmpty())
                    <p class="p-6 text-gray-600">{{ __('reports.no_results') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1000px] text-left text-sm">
                            <thead class="bg-gray-50 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                <tr>
                                    <th class="p-3">{{ __('reports.number') }}</th>
                                    <th class="p-3">{{ __('reports.letter_number') }}</th>
                                    <th class="p-3">{{ __('reports.agenda_number') }}</th>
                                    <th class="p-3">{{ __('reports.subject') }}</th>
                                    <th class="p-3">{{ $this->getReportType() === 'incoming' ? __('reports.sender') : __('reports.recipient') }}</th>
                                    <th class="p-3">{{ __('reports.classification') }}</th>
                                    <th class="p-3">{{ __('reports.letter_date') }}</th>
                                    <th class="p-3">{{ $this->getReportType() === 'incoming' ? __('reports.received_date') : __('reports.sent_date') }}</th>
                                    <th class="p-3">{{ __('reports.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $index => $letter)
                                    <tr class="border-t border-gray-200 dark:border-gray-700">
                                        <td class="p-3">{{ $rows->firstItem() + $index }}</td>
                                        <td class="p-3">{{ $letter->letter_number }}</td>
                                        <td class="p-3">{{ $letter->agenda_number }}</td>
                                        <td class="p-3">{{ $letter->subject }}</td>
                                        <td class="p-3">{{ $this->getReportType() === 'incoming' ? $letter->sender_name : $letter->recipient }}</td>
                                        <td class="p-3">{{ $letter->classification?->code }}</td>
                                        <td class="p-3">{{ $letter->letter_date?->format('d-m-Y') }}</td>
                                        <td class="p-3">{{ $this->getReportType() === 'incoming' ? $letter->received_at?->format('d-m-Y') : $letter->sent_date?->format('d-m-Y') }}</td>
                                        <td class="p-3">{{ $letter->note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $rows->links() }}</div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
