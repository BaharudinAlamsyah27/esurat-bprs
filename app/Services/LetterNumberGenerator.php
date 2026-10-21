<?php

namespace App\Services;

use App\Models\LetterSequence;
use App\Models\OutgoingLetter;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterNumberGenerator
{
    /** @return array{agenda_year: int, agenda_seq: int, agenda_number: string, letter_number: string} */
    public function generate(int $year, int $month, string $classificationCode, ?string $manualNumber = null): array
    {
        return DB::transaction(function () use ($year, $month, $classificationCode, $manualNumber): array {
            $agendaSequence = $this->nextSequence('outgoing_agenda', $year);
            $agendaNumber = str_pad((string) $agendaSequence, 4, '0', STR_PAD_LEFT).'/'.$year;
            $manual = filled($manualNumber) ? trim((string) $manualNumber) : null;

            return [
                'agenda_year' => $year,
                'agenda_seq' => $agendaSequence,
                'agenda_number' => $agendaNumber,
                'letter_number' => $manual ?? $this->nextAvailableLetterNumber($classificationCode, $month, $year),
            ];
        });
    }

    public function preview(int $year, int $month, string $classificationCode): string
    {
        $sequence = (int) LetterSequence::query()
            ->where('category', 'outgoing_number')
            ->where('scope_key', (string) $year)
            ->value('last_number');

        do {
            $sequence++;
            $preview = $this->formatNumber($sequence, $classificationCode, $month, $year);
        } while (OutgoingLetter::withTrashed()->where('letter_number', $preview)->exists());

        return $preview;
    }

    private function nextSequence(string $category, int $year): int
    {
        $now = now();

        DB::table('letter_sequences')->insertOrIgnore([
            'category' => $category,
            'scope_key' => (string) $year,
            'last_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sequence = LetterSequence::query()
            ->where('category', $category)
            ->where('scope_key', (string) $year)
            ->lockForUpdate()
            ->firstOrFail();

        $next = (int) $sequence->last_number + 1;
        $sequence->forceFill(['last_number' => $next])->save();

        return $next;
    }

    private function nextAvailableLetterNumber(string $classificationCode, int $month, int $year): string
    {
        do {
            $sequence = $this->nextSequence('outgoing_number', $year);
            $letterNumber = $this->formatNumber($sequence, $classificationCode, $month, $year);
        } while (OutgoingLetter::withTrashed()->where('letter_number', $letterNumber)->exists());

        return $letterNumber;
    }

    private function formatNumber(int $sequence, string $classificationCode, int $month, int $year): string
    {
        $setting = Setting::query()->where('key', 'outgoing_number_pattern')->value('value');
        $decoded = is_string($setting) ? json_decode($setting, true) : $setting;
        $pattern = is_array($decoded) ? ($decoded['pattern'] ?? null) : null;
        $pattern = is_string($pattern) && trim($pattern) !== '' ? $pattern : (string) config('letters.outgoing_number_pattern');

        preg_match_all('/\{[^}]*\}/', $pattern, $tokens);
        $allowedTokens = ['{kode_klasifikasi}', '{bulan_romawi}', '{tahun}'];
        foreach ($tokens[0] as $token) {
            if (! in_array($token, $allowedTokens, true) && preg_match('/^\{seq:\d+\}$/', $token) !== 1) {
                throw ValidationException::withMessages(['letter_number' => __('outgoing.invalid_number_pattern')]);
            }
        }

        if ($tokens[0] === []) {
            throw ValidationException::withMessages(['letter_number' => __('outgoing.invalid_number_pattern')]);
        }

        $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

        $formatted = preg_replace_callback(
            '/\{seq:(\d+)\}|\{kode_klasifikasi\}|\{bulan_romawi\}|\{tahun\}/',
            static function (array $matches) use ($sequence, $classificationCode, $month, $year, $romans): string {
                return match (true) {
                    str_starts_with($matches[0], '{seq:') => str_pad((string) $sequence, (int) $matches[1], '0', STR_PAD_LEFT),
                    $matches[0] === '{kode_klasifikasi}' => $classificationCode,
                    $matches[0] === '{bulan_romawi}' => $romans[$month],
                    default => (string) $year,
                };
            },
            $pattern,
        );

        if (! is_string($formatted) || str_contains($formatted, '{') || str_contains($formatted, '}')) {
            throw ValidationException::withMessages(['letter_number' => __('outgoing.invalid_number_pattern')]);
        }

        return $formatted;
    }
}
