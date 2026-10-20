<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Disposisi {{ $letter->agenda_number }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
        .header { border-bottom: 3px solid #0f5c46; margin-bottom: 22px; padding-bottom: 12px; text-align: center; }
        .header h1 { color: #0f5c46; font-size: 17pt; margin: 0 0 5px; }
        .header p { color: #52606d; font-size: 9pt; margin: 0; }
        h2 { font-size: 14pt; margin: 18px 0; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        .details th, .details td { border: 1px solid #cbd2d9; padding: 8px; text-align: left; vertical-align: top; }
        .details th { background: #f3f5f4; width: 28%; }
        .instructions { margin-top: 22px; }
        .instructions td { border: 1px solid #cbd2d9; height: 32px; padding: 8px; }
        .signatures { margin-top: 55px; }
        .signatures td { padding: 8px 20px; text-align: center; vertical-align: bottom; width: 50%; }
        .signature-space { height: 70px; }
        .muted { color: #52606d; font-size: 9pt; margin-top: 24px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $institutionName }}</h1>
        @if (filled($institutionAddress))
            <p>{{ $institutionAddress }}</p>
        @endif
    </header>

    <h2>LEMBAR DISPOSISI SURAT MASUK</h2>

    <table class="details">
        <tr><th>Nomor agenda</th><td>{{ $letter->agenda_number }}</td></tr>
        <tr><th>Tanggal diterima</th><td>{{ $letter->received_at->format('d/m/Y') }}</td></tr>
        <tr><th>Tanggal surat</th><td>{{ $letter->letter_date->format('d/m/Y') }}</td></tr>
        <tr><th>Nomor surat</th><td>{{ $letter->letter_number ?: '—' }}</td></tr>
        <tr><th>Pengirim</th><td>{{ $letter->sender_name }}@if ($letter->sender_institution) — {{ $letter->sender_institution }}@endif</td></tr>
        <tr><th>Perihal</th><td>{{ $letter->subject }}</td></tr>
        <tr><th>Diteruskan kepada</th><td>{{ $letter->initialRecipient?->name ?: '—' }}</td></tr>
    </table>

    <table class="instructions">
        <tr><td style="background:#f3f5f4;font-weight:bold">Instruksi disposisi</td></tr>
        @foreach (['Untuk diketahui', 'Segera tindak lanjuti', 'Koordinasikan', 'Siapkan jawaban', 'Arsipkan'] as $instruction)
            <tr><td>□ {{ $instruction }}</td></tr>
        @endforeach
        <tr><td style="height:90px;vertical-align:top">Catatan:</td></tr>
    </table>

    <table class="signatures">
        <tr>
            <td>Penerima,<div class="signature-space"></div>(........................................)</td>
            <td>Pejabat pemberi disposisi,<div class="signature-space"></div>(........................................)</td>
        </tr>
    </table>

    <p class="muted">Dicetak dari E-Surat BPRS Sragen pada {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.</p>
</body>
</html>
