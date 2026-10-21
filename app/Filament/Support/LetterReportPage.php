<?php

namespace App\Filament\Support;

use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Services\LetterReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

/** @property Form $form */
abstract class LetterReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static string $view = 'filament.pages.letter-report';

    protected static ?string $navigationGroup = 'LAPORAN';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $hasGenerated = false;

    /** @var array<string, mixed>|null */
    public ?array $activeFilters = null;

    abstract protected function reportType(): string;

    abstract protected function reportTitle(): string;

    /** @return array<string, string> */
    abstract protected function dateOptions(): array;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => today()->startOfMonth()->toDateString(),
            'end_date' => today()->toDateString(),
            'date_field' => 'letter_date',
            'classification_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')->label(__('reports.start_date'))->required()->native(false),
                DatePicker::make('end_date')->label(__('reports.end_date'))->required()->native(false),
                Select::make('date_field')->label(__('reports.date_type'))->options($this->dateOptions())->required(),
                Select::make('classification_id')
                    ->label(__('reports.classification'))
                    ->options(fn (): array => Classification::query()->orderBy('code')->get()->mapWithKeys(
                        fn (Classification $classification): array => [$classification->id => $classification->code.' — '.$classification->name],
                    )->all())
                    ->searchable()
                    ->preload()
                    ->placeholder(__('reports.all_classifications')),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->activeFilters = app(LetterReportService::class)->validatedFilters($this->reportType(), $this->form->getState());
        $this->resetPage();
        $this->hasGenerated = true;
    }

    /** @return LengthAwarePaginator<int, IncomingLetter>|LengthAwarePaginator<int, OutgoingLetter> */
    public function getReportRows(): LengthAwarePaginator
    {
        if (! $this->hasGenerated || $this->activeFilters === null) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $reports = app(LetterReportService::class);

        return $this->reportType() === 'incoming'
            ? $reports->incomingQuery($this->activeFilters)->paginate(25)
            : $reports->outgoingQuery($this->activeFilters)->paginate(25);
    }

    public function getReportTitle(): string
    {
        return $this->reportTitle();
    }

    public function getReportType(): string
    {
        return $this->reportType();
    }

    /** @return array<string, mixed> */
    public function getReportFilters(): array
    {
        return $this->activeFilters ?? [];
    }

    public function getDateRangeLabel(): string
    {
        if ($this->activeFilters === null) {
            return '';
        }

        return Carbon::parse($this->activeFilters['start_date'])->format('d-m-Y').' s/d '.Carbon::parse($this->activeFilters['end_date'])->format('d-m-Y');
    }
}
