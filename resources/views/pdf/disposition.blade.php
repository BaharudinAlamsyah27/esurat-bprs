<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ __('disposition.pdf.title') }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2933; font-size: 9pt; line-height: 1.35; }
        .header { width: 100%; border-bottom: 3px solid #0f5c46; padding-bottom: 8px; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .logo { width: 62px; max-height: 62px; object-fit: contain; }
        .brand { font-size: 16pt; font-weight: bold; color: #0f5c46; }
        .contact { font-size: 8pt; margin-top: 3px; }
        h1 { margin: 12px 0; text-align: center; font-size: 15pt; letter-spacing: 1px; }
        .letter-info, .disposition-info { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #667085; padding: 6px 7px; vertical-align: top; }
        th { width: 22%; background: #f1f5f3; text-align: left; font-weight: bold; }
        .section { margin-top: 12px; font-weight: bold; color: #0f5c46; }
        .instruction { margin: 2px 0; }
        .check { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #0f5c46; }
        .signature { width: 42%; margin: 18px 0 0 auto; text-align: center; page-break-inside: avoid; }
        .signature-space { height: 58px; }
        .muted { color: #52606d; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        @if ($logoData)
            <td style="width: 72px"><img class="logo" src="{{ $logoData }}" alt="Logo instansi"></td>
        @endif
        <td>
            <div class="brand">{{ $institution?->name ?? 'BPRS Sragen' }}</div>
            @if ($institution?->address)<div class="contact">{{ $institution->address }}</div>@endif
            @if ($institution?->phone || $institution?->email)
                <div class="contact">{{ $institution?->phone }}@if ($institution?->phone && $institution?->email) · @endif{{ $institution?->email }}</div>
            @endif
        </td>
    </tr>
</table>

<h1>{{ __('disposition.pdf.title') }}</h1>

<table class="letter-info">
    <tr><th>{{ __('disposition.pdf.source') }}</th><td>{{ $letter->sender_name }}</td><th>{{ __('disposition.pdf.received_at') }}</th><td>{{ $letter->received_at->format('d-m-Y') }}</td></tr>
    <tr><th>{{ __('disposition.pdf.letter_number') }}</th><td>{{ $letter->letter_number }}</td><th>{{ __('disposition.pdf.agenda_number') }}</th><td>{{ $letter->agenda_number }}</td></tr>
    <tr><th>{{ __('disposition.pdf.letter_date') }}</th><td>{{ $letter->letter_date->format('d-m-Y') }}</td><th>{{ __('disposition.pdf.classification') }}</th><td>{{ $letter->classification?->code ?? '—' }}</td></tr>
    <tr><th>{{ __('disposition.pdf.subject') }}</th><td colspan="3">{{ $letter->subject }}</td></tr>
</table>

<div class="section">{{ __('disposition.title') }}</div>
<table class="disposition-info">
    <tr><th>{{ __('disposition.nature') }}</th><td>{{ $disposition->nature->label() }}</td><th>{{ __('disposition.date') }}</th><td>{{ $dispositionDate }}</td></tr>
    <tr><th>{{ __('disposition.pdf.forwarded_to') }}</th><td colspan="3">{!! nl2br(e(implode(', ', $disposition->forwarded_to ?? []))) !!}</td></tr>
    <tr>
        <th>{{ __('disposition.pdf.instructions') }}</th>
        <td colspan="3">
            @foreach ($instructionLabels as $label)
                <div class="instruction"><span class="check">☑</span> {{ $label }}</div>
            @endforeach
        </td>
    </tr>
    <tr><th>{{ __('disposition.pdf.note') }}</th><td colspan="3">{!! filled($disposition->note) ? nl2br(e($disposition->note)) : '<span class="muted">—</span>' !!}</td></tr>
</table>

<div class="signature">
    <div>{{ $institution?->city ?: 'Sragen' }}, {{ $dispositionDate }}</div>
    <div>{{ $institution?->leader_position ?: __('disposition.pdf.signature') }}</div>
    <div class="signature-space"></div>
    <strong>{{ $institution?->leader_name ?: '................................' }}</strong>
</div>
</body>
</html>
