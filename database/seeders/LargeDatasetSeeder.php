<?php

namespace Database\Seeders;

use App\Enums\IncomingLetterStatus;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LargeDatasetSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LargeDatasetSeeder hanya boleh dijalankan di local atau testing.');
        }

        if (IncomingLetter::query()->where('letter_number', 'like', 'M6-DEMO-IN-%')->exists()
            || OutgoingLetter::query()->where('letter_number', 'like', 'M6-DEMO-OUT-%')->exists()) {
            $this->command?->warn('Data uji M6 sudah ada; seeder dilewati agar tidak membuat data ganda.');

            return;
        }

        $classificationIds = Classification::query()->pluck('id')->all();
        $userId = User::query()->where('is_active', true)->value('id');

        if ($classificationIds === [] || $userId === null) {
            throw new RuntimeException('Buat klasifikasi dan satu pengguna aktif sebelum menjalankan LargeDatasetSeeder.');
        }

        $currentYear = (int) now()->year;
        $years = [$currentYear - 1, $currentYear];
        $incomingSequences = $this->lastSequences(IncomingLetter::class, $years);
        $outgoingSequences = $this->lastSequences(OutgoingLetter::class, $years);

        DB::transaction(function () use ($classificationIds, $userId, $years, $incomingSequences, $outgoingSequences): void {
            $this->insertIncoming($classificationIds, (int) $userId, $years, $incomingSequences);
            $this->insertOutgoing($classificationIds, (int) $userId, $years, $outgoingSequences);
        });

        $this->command?->info('Selesai membuat 5.000 surat masuk dan 5.000 surat keluar untuk uji kinerja.');
    }

    /**
     * @param  class-string<IncomingLetter|OutgoingLetter>  $model
     * @param  array<int, int>  $years
     * @return array<int, int>
     */
    private function lastSequences(string $model, array $years): array
    {
        $last = $model::query()
            ->select('agenda_year')
            ->selectRaw('MAX(agenda_seq) as last_sequence')
            ->whereIn('agenda_year', $years)
            ->groupBy('agenda_year')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->agenda_year => (int) $row->last_sequence])
            ->all();

        return array_replace(array_fill_keys($years, 0), $last);
    }

    /** @param array<int, int> $classificationIds
     * @param  array<int, int>  $years
     * @param  array<int, int>  $sequences
     */
    private function insertIncoming(array $classificationIds, int $userId, array $years, array $sequences): void
    {
        $rows = [];
        for ($i = 1; $i <= 5000; $i++) {
            $year = $years[$i <= 2500 ? 0 : 1];
            $sequence = ++$sequences[$year];
            $letterDate = $this->sampleDate($year, $i);
            $rows[] = [
                'agenda_year' => $year,
                'agenda_seq' => $sequence,
                'agenda_number' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT).'/'.$year,
                'received_at' => Carbon::parse($letterDate)->addDays(random_int(0, 4))->toDateString(),
                'letter_date' => $letterDate,
                'letter_number' => sprintf('M6-DEMO-IN-%d-%05d', $year, $sequence),
                'sender_name' => fake()->company(),
                'sender_institution' => fake()->optional()->company(),
                'subject' => fake()->sentence(5),
                'note' => null,
                'classification_id' => $classificationIds[array_rand($classificationIds)],
                'attachments_count' => 0,
                'status' => IncomingLetterStatus::Registered->value,
                'registered_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) === 500) {
                DB::table('incoming_letters')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('incoming_letters')->insert($rows);
        }
    }

    /** @param array<int, int> $classificationIds
     * @param  array<int, int>  $years
     * @param  array<int, int>  $sequences
     */
    private function insertOutgoing(array $classificationIds, int $userId, array $years, array $sequences): void
    {
        $rows = [];
        for ($i = 1; $i <= 5000; $i++) {
            $year = $years[$i <= 2500 ? 0 : 1];
            $sequence = ++$sequences[$year];
            $letterDate = $this->sampleDate($year, $i);
            $rows[] = [
                'agenda_year' => $year,
                'agenda_seq' => $sequence,
                'agenda_number' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT).'/'.$year,
                'letter_number' => sprintf('M6-DEMO-OUT-%d-%05d', $year, $sequence),
                'recipient' => fake()->company(),
                'subject' => fake()->sentence(5),
                'note' => null,
                'classification_id' => $classificationIds[array_rand($classificationIds)],
                'letter_date' => $letterDate,
                'sent_date' => Carbon::parse($letterDate)->addDays(random_int(0, 2))->toDateString(),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) === 500) {
                DB::table('outgoing_letters')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('outgoing_letters')->insert($rows);
        }
    }

    private function sampleDate(int $year, int $index): string
    {
        $month = (($index - 1) % 12) + 1;
        $day = random_int(1, Carbon::create($year, $month, 1)->daysInMonth);

        return Carbon::create($year, $month, $day)->toDateString();
    }
}
