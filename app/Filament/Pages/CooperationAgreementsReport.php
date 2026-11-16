<?php

namespace App\Filament\Pages;

use App\Models\CooperationAgreement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

/**
 * @property Form $form
 */
class CooperationAgreementsReport extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static string $view = 'filament.pages.cooperation-agreements-report';

    protected static ?string $navigationGroup = 'LAPORAN';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, string> */
    public array $activeFilters = [];

    public bool $hasGenerated = false;

    public static function getNavigationLabel(): string
    {
        return __('reports.cooperation_navigation');
    }

    public static function getSlug(): string
    {
        return 'laporan/perjanjian-kerjasama';
    }

    public function getTitle(): string
    {
        return __('reports.cooperation_title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')->label(__('reports.start_date'))->required(),
                DatePicker::make('end_date')->label(__('reports.end_date'))->required()->afterOrEqual('start_date'),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function generate(): void
    {
        $state = $this->form->getState();
        $start = Carbon::parse($state['start_date'])->startOfDay();
        $end = Carbon::parse($state['end_date'])->startOfDay();

        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages(['data.end_date' => __('reports.date_range_too_long')]);
        }

        $this->activeFilters = [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
        $this->hasGenerated = true;
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, CooperationAgreement> */
    public function getReportRows(): LengthAwarePaginator
    {
        return CooperationAgreement::query()
            ->whereDate('agreement_date', '>=', $this->activeFilters['start_date'])
            ->whereDate('agreement_date', '<=', $this->activeFilters['end_date'])
            ->orderBy('agreement_date')
            ->orderBy('id')
            ->paginate(25);
    }

    public function getDateRangeLabel(): string
    {
        return Carbon::parse($this->activeFilters['start_date'])->format('d-m-Y')
            .' – '.Carbon::parse($this->activeFilters['end_date'])->format('d-m-Y');
    }
}
