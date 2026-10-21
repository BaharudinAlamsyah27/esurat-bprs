<?php

namespace App\Enums;

enum DispositionInstruction: string
{
    case UntukDiketahui = 'untuk_diketahui';
    case SegeraTindakLanjuti = 'segera_tindak_lanjuti';
    case Koordinasikan = 'koordinasikan';
    case SiapkanKonsepJawaban = 'siapkan_konsep_jawaban';
    case MohonPendapat = 'mohon_pendapat';
    case Arsipkan = 'arsipkan';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return __('disposition.instructions.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
