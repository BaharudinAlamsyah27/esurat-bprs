<?php

namespace App\Filament\Widgets;

use App\Enums\DispositionRecipientStatus;
use App\Filament\Resources\IncomingLetterResource;
use App\Models\DispositionRecipient;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class MyDispositionsWidget extends TableWidget
{
    protected static ?int $sort = -1;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('dispositions.my_tasks');
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('disposition.update_own') ?? false;
    }

    /** @return Builder<DispositionRecipient> */
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return DispositionRecipient::query()->whereRaw('1 = 0');
        }

        return DispositionRecipient::query()
            ->with(['disposition.sender', 'disposition.incomingLetter'])
            ->whereIn('disposition_recipients.status', [DispositionRecipientStatus::Pending->value, DispositionRecipientStatus::Read->value, DispositionRecipientStatus::InProgress->value])
            ->where(function (Builder $query) use ($user): void {
                $query->where('recipient_user_id', $user->id);
                if ($user->unit_id !== null) {
                    $query->orWhere('recipient_unit_id', $user->unit_id);
                }
            })
            ->join('dispositions', 'disposition_recipients.disposition_id', '=', 'dispositions.id')
            ->orderByRaw('dispositions.due_at IS NULL')
            ->orderBy('dispositions.due_at')
            ->select('disposition_recipients.*');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('disposition.incomingLetter.agenda_number')->label(__('incoming.agenda_number')),
                TextColumn::make('disposition.incomingLetter.subject')->label(__('incoming.subject'))->limit(45),
                TextColumn::make('disposition.sender.name')->label(__('dispositions.from')),
                TextColumn::make('disposition.due_at')->label(__('dispositions.due_at'))->dateTime('d M Y H:i')->placeholder('—')
                    ->color(fn (DispositionRecipient $record): string => $record->disposition->due_at?->isPast() ? 'danger' : 'gray')
                    ->description(fn (DispositionRecipient $record): ?string => $record->disposition->due_at?->isPast() ? __('dispositions.overdue') : null),
                TextColumn::make('status')->label(__('dispositions.status'))->badge()
                    ->formatStateUsing(fn (DispositionRecipientStatus $state): string => $state->getLabel())
                    ->color(fn (DispositionRecipientStatus $state): string => $state->getColor()),
            ])
            ->recordUrl(fn (DispositionRecipient $record): string => IncomingLetterResource::getUrl('view', ['record' => $record->disposition->incoming_letter_id]))
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('dispositions.no_tasks'));
    }
}
