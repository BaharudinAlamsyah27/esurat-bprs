<?php

namespace App\Filament\Widgets;

use App\Models\Classification;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class LettersByClassificationChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '150px';

    public function getHeading(): string
    {
        return __('app.chart_classification_title');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'layout' => ['padding' => 12],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'padding' => 18],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        return Cache::remember('dashboard.classification-chart-description.v1', now()->addSeconds(60), function (): string {
            $items = $this->topClassifications();
            if ($items === []) {
                return __('app.no_data');
            }

            return collect($items)->map(fn (array $item): string => $item['label'].' ('.$item['value'].')')->implode(' · ');
        });
    }

    /** @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} */
    protected function getData(): array
    {
        return Cache::remember('dashboard.classification-chart.v1', now()->addSeconds(60), function (): array {
            $items = $this->topClassifications();

            return [
                'labels' => array_column($items, 'label'),
                'datasets' => [[
                    'data' => array_column($items, 'value'),
                    'backgroundColor' => ['#0F5C46', '#B8912F', '#2B6CB0', '#2F855A', '#C77700'],
                    'borderWidth' => 2,
                ]],
            ];
        });
    }

    /** @return array<int, array{label: string, value: int}> */
    private function topClassifications(): array
    {
        return Classification::query()
            ->withCount(['incomingLetters', 'outgoingLetters'])
            ->get()
            ->map(fn (Classification $classification): array => [
                'label' => $classification->code.' — '.$classification->name,
                'value' => $classification->incoming_letters_count + $classification->outgoing_letters_count,
            ])
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();
    }

    /** @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} */
    public function getChartData(): array
    {
        return $this->getCachedData();
    }
}
