<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Actions\ArchiveIncomingLetter;
use App\Actions\CloseDispositionManually;
use App\Actions\CreateDisposition;
use App\Actions\ForwardIncomingLetter;
use App\Actions\UpdateRecipientStatus;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Filament\Resources\IncomingLetterResource;
use App\Models\Disposition;
use App\Models\DispositionInstruction;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\Unit;
use App\Models\User;
use App\Services\DispositionNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\UploadedFile;

class ViewIncomingLetter extends ViewRecord
{
    protected static string $resource = IncomingLetterResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $this->currentIncomingLetter()->dispositions()->with('recipients')->get()->flatMap->recipients
            ->filter(fn (DispositionRecipient $recipient): bool => $recipient->status === DispositionRecipientStatus::Pending
                && ($recipient->recipient_user_id === $user->id || ($user->unit_id !== null && $recipient->recipient_unit_id === $user->unit_id)))
            ->each(fn (DispositionRecipient $recipient) => app(UpdateRecipientStatus::class)->execute($user, $recipient, DispositionRecipientStatus::Read));
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('incoming.detail'))
                ->columns(2)
                ->schema([
                    TextEntry::make('agenda_number')->label(__('incoming.agenda_number')),
                    TextEntry::make('status')
                        ->label(__('incoming.status'))
                        ->badge()
                        ->formatStateUsing(fn (IncomingLetter $record): string => $record->status->getLabel())
                        ->color(fn (IncomingLetter $record): string => $record->status->getColor()),
                    TextEntry::make('received_at')->label(__('incoming.received_at'))->date('d M Y'),
                    TextEntry::make('letter_date')->label(__('incoming.letter_date'))->date('d M Y'),
                    TextEntry::make('subject')
                        ->label(__('incoming.subject'))
                        ->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_subject') : $state),
                    TextEntry::make('letter_number')->label(__('incoming.letter_number'))->placeholder('—'),
                    TextEntry::make('sender_name')->label(__('incoming.sender_name'))->formatStateUsing(fn (string $state, IncomingLetter $record): string => $record->isMetadataOnlyFor(auth()->user()) ? __('incoming.masked_sender') : $state)->visible($this->canViewContents(...)),
                    TextEntry::make('sender_institution')->label(__('incoming.sender_institution'))->visible($this->canViewContents(...)),
                    TextEntry::make('initialRecipient.name')->label(__('incoming.initial_recipient'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('letterType.name')->label(__('incoming.letter_type'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('classification.name')->label(__('incoming.classification'))->placeholder('—')->visible($this->canViewContents(...)),
                    TextEntry::make('urgency')->label(__('incoming.urgency'))->badge()->visible($this->canViewContents(...)),
                    TextEntry::make('confidentiality')->label(__('incoming.confidentiality'))->badge()->visible($this->canViewContents(...)),
                    TextEntry::make('delivery_method')->label(__('incoming.delivery_method'))->visible($this->canViewContents(...)),
                    TextEntry::make('summary')->label(__('incoming.summary'))->columnSpanFull()->visible($this->canViewContents(...)),
                    TextEntry::make('registered_by')->label(__('incoming.registered_by'))->formatStateUsing(fn (IncomingLetter $record): string => $record->registrar->name)->visible($this->canViewContents(...)),
                    RepeatableEntry::make('attachments')
                        ->label(__('incoming.attachments'))
                        ->schema([
                            TextEntry::make('original_name')
                                ->label(__('incoming.scans'))
                                ->url(fn ($record): string => route('incoming-letters.attachments.show', $record))
                                ->openUrlInNewTab(),
                        ])
                        ->columnSpanFull()
                        ->visible(fn (IncomingLetter $record): bool => auth()->user()?->can('viewAttachments', $record) ?? false),
                ]),
            Section::make(__('dispositions.timeline'))
                ->schema([
                    RepeatableEntry::make('dispositions')
                        ->label(__('dispositions.timeline'))
                        ->schema([
                            TextEntry::make('sender.name')->label(__('dispositions.from')),
                            TextEntry::make('status')->badge()->formatStateUsing(fn ($state): string => $state->getLabel())->color(fn ($state): string => $state->getColor()),
                            TextEntry::make('notes')->label(__('dispositions.notes'))->placeholder('—'),
                            TextEntry::make('due_at')->label(__('dispositions.due_at'))->dateTime('d M Y H:i')->placeholder('—'),
                            RepeatableEntry::make('instructions')->schema([TextEntry::make('text')])->label(__('dispositions.instructions')),
                            RepeatableEntry::make('recipients')->schema([
                                TextEntry::make('recipient.name')->label(__('dispositions.recipient'))->placeholder(fn ($record): string => $record->unit->name ?? '—'),
                                TextEntry::make('status')->badge()->formatStateUsing(fn ($state): string => $state->getLabel())->color(fn ($state): string => $state->getColor()),
                                TextEntry::make('report_notes')->label(__('dispositions.report'))->placeholder('—'),
                            ])->label(__('dispositions.recipients')),
                            RepeatableEntry::make('attachments')->schema([
                                TextEntry::make('original_name')->label(__('dispositions.report_file'))
                                    ->url(fn ($record): string => route('incoming-letters.attachments.show', $record))
                                    ->openUrlInNewTab(),
                            ])->label(__('dispositions.report_file')),
                            RepeatableEntry::make('children')->schema([
                                TextEntry::make('sender.name')->label(__('dispositions.from')),
                                TextEntry::make('notes')->label(__('dispositions.notes'))->placeholder('—'),
                                TextEntry::make('status')->badge()->formatStateUsing(fn ($state): string => $state->getLabel())->color(fn ($state): string => $state->getColor()),
                            ])->label(__('dispositions.sub_dispositions')),
                        ])
                        ->columnSpanFull(),
                ])
                ->visible(fn (IncomingLetter $record): bool => $this->canViewDispositionTimeline($record)),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDisposition')
                ->label(__('dispositions.create'))
                ->icon('heroicon-o-arrow-turn-down-right')
                ->form([
                    Select::make('parent_id')
                        ->label(__('dispositions.parent'))
                        ->helperText(__('dispositions.parent_help'))
                        ->options(fn (): array => $this->eligibleParents())
                        ->required(fn (): bool => $this->currentIncomingLetter()->initial_recipient_id !== auth()->id())
                        ->visible(fn (): bool => $this->currentIncomingLetter()->initial_recipient_id !== auth()->id())
                        ->searchable(),
                    Select::make('user_ids')
                        ->label(__('dispositions.users'))
                        ->helperText(__('dispositions.users_help'))
                        ->multiple()
                        ->searchable()
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['direksi', 'kepala_unit', 'staf']))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()),
                    Select::make('unit_ids')
                        ->label(__('dispositions.units'))
                        ->helperText(__('dispositions.units_help'))
                        ->multiple()
                        ->searchable()
                        ->options(fn (): array => Unit::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
                    Select::make('instruction_ids')->label(__('dispositions.instructions'))->multiple()->options(fn (): array => DispositionInstruction::query()->where('is_active', true)->orderBy('sort_order')->pluck('text', 'id')->all()),
                    Textarea::make('notes')->label(__('dispositions.notes'))->rows(3)->maxLength(3000),
                    Select::make('urgency')->label(__('dispositions.urgency'))->options(__('incoming.urgencies'))->default('biasa')->required(),
                    DateTimePicker::make('due_at')->label(__('dispositions.due_at'))->seconds(false),
                ])
                ->visible(fn (): bool => $this->canCreateDisposition())
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if (! $actor instanceof User) {
                        return;
                    }
                    $parentId = filled($data['parent_id'] ?? null) ? $data['parent_id'] : null;
                    $parent = $parentId === null ? null : Disposition::query()->findOrFail($parentId);
                    $disposition = app(CreateDisposition::class)->execute($actor, $this->currentIncomingLetter(), $data, $parent);
                    app(DispositionNotificationService::class)->notifyCreated($disposition);
                    Notification::make()->title(__('dispositions.created'))->success()->send();
                }),
            Action::make('updateDisposition')
                ->label(__('dispositions.update_status'))
                ->icon('heroicon-o-check-circle')
                ->form([
                    Select::make('recipient_id')
                        ->label(__('dispositions.recipient'))
                        ->required()
                        ->default(function (): int|string|null {
                            $options = $this->ownRecipientOptions();

                            return count($options) === 1 ? array_key_first($options) : null;
                        })
                        ->live()
                        ->options(fn (): array => $this->ownRecipientOptions()),
                    Select::make('status')->label(__('dispositions.status'))->options(fn (Get $get): array => $this->allowedStatusOptions($get('recipient_id')))->required(),
                    Textarea::make('report_notes')->label(__('dispositions.report'))->rows(3)->maxLength(3000),
                    FileUpload::make('report_file')->label(__('dispositions.report_file'))->storeFiles(false)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(10240),
                ])
                ->visible(fn (): bool => $this->ownRecipientOptions() !== [])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if (! $actor instanceof User) {
                        return;
                    }
                    $recipient = DispositionRecipient::query()->with('disposition')->findOrFail($data['recipient_id']);
                    $file = $data['report_file'] ?? null;
                    app(UpdateRecipientStatus::class)->execute($actor, $recipient, DispositionRecipientStatus::from($data['status']), $data['report_notes'] ?? null, $file instanceof UploadedFile ? $file : null);
                    Notification::make()->title(__('dispositions.updated'))->success()->send();
                }),
            Action::make('closeDisposition')
                ->label(__('dispositions.close'))
                ->color('warning')
                ->form([Textarea::make('reason')->label(__('dispositions.close_reason'))->required()->minLength(5)->maxLength(3000)])
                ->visible(fn (): bool => auth()->user()?->can('close', $this->getRecord()) ?? false)
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    if ($actor instanceof User) {
                        app(CloseDispositionManually::class)->execute($actor, $this->currentIncomingLetter()->dispositions()->firstOrFail(), $data['reason']);
                    }
                    Notification::make()->title(__('dispositions.closed'))->success()->send();
                }),
            Action::make('print')
                ->label(__('incoming.print'))
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('incoming-letters.print', $this->getRecord()))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('viewContents', $this->getRecord()) ?? false),
            Action::make('forward')
                ->label(__('incoming.forward'))
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription(__('incoming.forward_confirmation'))
                ->visible(fn (): bool => auth()->user()?->can('forward', $this->getRecord()) ?? false)
                ->action(function (): void {
                    /** @var User $user */
                    $user = auth()->user();
                    app(ForwardIncomingLetter::class)->execute($user, $this->currentIncomingLetter());
                    $this->currentIncomingLetter()->refresh();
                    Notification::make()->title(__('incoming.forwarded'))->success()->send();
                }),
            Action::make('archive')
                ->label(__('incoming.archive'))
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('incoming.archive_confirmation'))
                ->form([
                    TextInput::make('archive_location')->label(__('incoming.archive_location'))->maxLength(255),
                ])
                ->visible(fn (): bool => auth()->user()?->can('archive', $this->getRecord()) ?? false)
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = auth()->user();
                    app(ArchiveIncomingLetter::class)->execute($user, $this->currentIncomingLetter(), $data['archive_location'] ?? null);
                    $this->currentIncomingLetter()->refresh();
                    Notification::make()->title(__('incoming.archived'))->success()->send();
                }),
        ];
    }

    private function canViewContents(IncomingLetter $record): bool
    {
        return auth()->user()?->can('viewContents', $record) ?? false;
    }

    private function currentIncomingLetter(): IncomingLetter
    {
        $record = $this->getRecord();

        if (! $record instanceof IncomingLetter) {
            abort(404);
        }

        return $record;
    }

    private function canCreateDisposition(): bool
    {
        $user = auth()->user();
        $letter = $this->currentIncomingLetter();
        $canReceiveDisposition = in_array($letter->status, [
            IncomingLetterStatus::Forwarded,
            IncomingLetterStatus::InDisposition,
            IncomingLetterStatus::InProgress,
        ], true);

        return $user instanceof User && $canReceiveDisposition && $user->can('disposition.create') && $user->can('viewContents', $letter)
            && ((int) $letter->initial_recipient_id === (int) $user->id || $this->eligibleParents() !== []);
    }

    private function canViewDispositionTimeline(IncomingLetter $letter): bool
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->can('viewContents', $letter)) {
            return false;
        }

        return $user->hasRole('direksi') || $letter->dispositions()->where(function ($query) use ($user): void {
            $query->where('from_user_id', $user->id)->orWhereHas('recipients', function ($recipients) use ($user): void {
                $recipients->where('recipient_user_id', $user->id);
                if ($user->unit_id !== null) {
                    $recipients->orWhere('recipient_unit_id', $user->unit_id);
                }
            });
        })->exists();
    }

    /** @return array<int, string> */
    private function eligibleParents(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        return $this->currentIncomingLetter()->dispositions()->whereHas('recipients', function ($query) use ($user): void {
            $query->whereIn('status', [
                DispositionRecipientStatus::Pending->value,
                DispositionRecipientStatus::Read->value,
                DispositionRecipientStatus::InProgress->value,
            ])->where(function ($involvement) use ($user): void {
                $involvement->where('recipient_user_id', $user->id);
                if ($user->unit_id !== null) {
                    $involvement->orWhere('recipient_unit_id', $user->unit_id);
                }
            });
        })->with('sender')->get()->mapWithKeys(fn (Disposition $disposition): array => [$disposition->id => ($disposition->sender->name ?? '').' — '.str($disposition->notes)->limit(55)])->all();
    }

    /** @return array<int, string> */
    private function ownRecipientOptions(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        return $this->currentIncomingLetter()->dispositions()
            ->with(['sender', 'recipients.recipient', 'recipients.unit'])
            ->get()
            ->flatMap->recipients
            ->filter(fn (DispositionRecipient $recipient): bool => in_array($recipient->status, [DispositionRecipientStatus::Pending, DispositionRecipientStatus::Read, DispositionRecipientStatus::InProgress], true)
                && ((int) $recipient->recipient_user_id === (int) $user->id || ($user->unit_id !== null && (int) $recipient->recipient_unit_id === (int) $user->unit_id)))
            ->mapWithKeys(fn (DispositionRecipient $recipient): array => [$recipient->id => __(
                'dispositions.recipient_option',
                [
                    'recipient' => $recipient->recipient?->name ?? $recipient->unit?->name ?? __('dispositions.recipient_unknown'),
                    'status' => $recipient->status->getLabel(),
                    'sender' => $recipient->disposition->sender->name ?? '—',
                ],
            )])->all();
    }

    /** @return array<string, string> */
    private function allowedStatusOptions(mixed $recipientId): array
    {
        if (! is_numeric($recipientId) || ! array_key_exists((int) $recipientId, $this->ownRecipientOptions())) {
            return [];
        }

        $recipient = DispositionRecipient::query()->find($recipientId);
        if (! $recipient instanceof DispositionRecipient) {
            return [];
        }

        $next = match ($recipient->status) {
            DispositionRecipientStatus::Pending => [DispositionRecipientStatus::Read],
            DispositionRecipientStatus::Read => [DispositionRecipientStatus::InProgress],
            DispositionRecipientStatus::InProgress => [DispositionRecipientStatus::Done],
            default => [],
        };

        return collect($next)->mapWithKeys(fn (DispositionRecipientStatus $status): array => [$status->value => $status->getLabel()])->all();
    }
}
