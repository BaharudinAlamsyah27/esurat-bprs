<?php

namespace App\Models;

use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property DispositionNature $nature
 * @property list<string>|null $forwarded_to
 * @property list<string>|null $instructions
 * @property Carbon $disposition_date
 * @property string|null $instruction_other
 * @property string|null $note
 * @property int $print_count
 */
class Disposition extends Model
{
    protected $fillable = [
        'incoming_letter_id', 'nature', 'forwarded_to', 'instructions', 'instruction_other', 'note',
        'disposition_date', 'print_count', 'printed_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'nature' => DispositionNature::class,
            'forwarded_to' => 'array',
            'instructions' => 'array',
            'disposition_date' => 'date',
            'printed_at' => 'datetime',
            'print_count' => 'integer',
        ];
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return list<string> */
    public function instructionLabels(): array
    {
        $labels = [];
        $instructions = $this->instructions ?? [];

        foreach ($instructions as $instruction) {
            $enum = DispositionInstruction::tryFrom($instruction);

            if ($enum instanceof DispositionInstruction && $enum !== DispositionInstruction::Lainnya) {
                $labels[] = $enum->label();
            }
        }

        if (in_array(DispositionInstruction::Lainnya->value, $instructions, true) && filled($this->instruction_other)) {
            $labels[] = $this->instruction_other;
        }

        return $labels;
    }
}
