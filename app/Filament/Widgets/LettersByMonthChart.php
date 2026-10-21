<?php

namespace App\Filament\Widgets;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use stdClass;

class LettersByMonthChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '150px';

    public function getHeading(): string
    {
        return __('app.chart_monthly_title');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        return __('app.chart_monthly_description');
    }

    /** @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} */
    protected function getData(): array
    {
        return Cache::remember('dashboard.monthly-letters.v1', now()->addSeconds(60), function (): array {
            $months = collect(range(0, 5))->map(fn (int $offset): Carbon => today()->startOfMonth()->subMonths(5 - $offset));
            $start = $months->first();
            $end = $months->last()?->copy()->endOfMonth();
            $labels = $months->map(fn (Carbon $month): string => $month->translatedFormat('M y'))->all();
            $totals = [];
            $yearExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "strftime('%Y', letter_date)"
                : 'YEAR(letter_date)';
            $monthExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "strftime('%m', letter_date)"
                : 'MONTH(letter_date)';

            foreach (['incoming' => IncomingLetter::class, 'outgoing' => OutgoingLetter::class] as $key => $model) {
                $totals[$key] = $model::query()
                    ->selectRaw("{$yearExpression} as report_year, {$monthExpression} as report_month, COUNT(*) as total")
                    ->whereBetween('letter_date', [$start->toDateString(), $end?->toDateString()])
                    ->groupByRaw("{$yearExpression}, {$monthExpression}")
                    ->toBase()
                    ->get()
                    ->mapWithKeys(fn (stdClass $row): array => [sprintf('%04d-%02d', $row->report_year, $row->report_month) => (int) $row->total])
                    ->all();
            }

            $monthKeys = $months->map(fn (Carbon $month): string => $month->format('Y-m'))->all();

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => __('app.incoming_total'),
                        'data' => array_map(fn (string $month): int => $totals['incoming'][$month] ?? 0, $monthKeys),
                        'backgroundColor' => '#0F5C46',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => __('app.outgoing_total'),
                        'data' => array_map(fn (string $month): int => $totals['outgoing'][$month] ?? 0, $monthKeys),
                        'backgroundColor' => '#B8912F',
                        'borderRadius' => 6,
                    ],
                ],
            ];
        });
    }

    /** @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} */
    public function getChartData(): array
    {
        return $this->getCachedData();
    }
}
