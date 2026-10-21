<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 16mm; }
        body { color: #1f2933; font: 10pt Arial, sans-serif; }
        .heading { border-bottom: 2px solid #0f5c46; margin-bottom: 18px; padding-bottom: 10px; }
        .heading h1 { color: #0f5c46; font-size: 15pt; margin: 0 0 3px; }
        h2 { font-size: 13pt; margin: 18px 0 4px; text-align: center; }
        p { margin: 3px 0 12px; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8b9298; padding: 6px; vertical-align: top; }
        th { background: #edf2f0; text-align: left; }
        .number { text-align: right; }
        @media screen { body { margin: 24px auto; max-width: 1200px; } .print-button { margin-bottom: 14px; } }
        @media print { .print-button { display: none; } }
    </style>
</head>
<body>
    <button class="print-button" type="button" onclick="window.print()">{{ __('reports.print') }}</button>
    <header class="heading">
        <h1>{{ $institution?->name ?? __('app.name') }}</h1>
        @if ($institution?->address)<div>{{ $institution->address }}</div>@endif
        @if ($institution?->phone)<div>{{ __('reports.phone') }}: {{ $institution->phone }}</div>@endif
    </header>
    <h2>{{ $title }}</h2>
    <p>{{ __('reports.period') }}: {{ \Illuminate\Support\Carbon::parse($filters['start_date'])->format('d-m-Y') }} s/d {{ \Illuminate\Support\Carbon::parse($filters['end_date'])->format('d-m-Y') }}</p>
    <table>
        <thead><tr>
            <th>{{ __('reports.number') }}</th><th>{{ __('reports.letter_number') }}</th><th>{{ __('reports.agenda_number') }}</th>
            <th>{{ __('reports.subject') }}</th><th>{{ $type === 'incoming' ? __('reports.sender') : __('reports.recipient') }}</th>
            <th>{{ __('reports.classification') }}</th><th>{{ __('reports.letter_date') }}</th>
            <th>{{ $type === 'incoming' ? __('reports.received_date') : __('reports.sent_date') }}</th><th>{{ __('reports.note') }}</th>
        </tr></thead>
        <tbody>
        @forelse ($letters as $index => $letter)
            <tr>
                <td class="number">{{ $index + 1 }}</td><td>{{ $letter->letter_number }}</td><td>{{ $letter->agenda_number }}</td>
                <td>{{ $letter->subject }}</td><td>{{ $type === 'incoming' ? $letter->sender_name : $letter->recipient }}</td>
                <td>{{ $letter->classification?->code }}</td><td>{{ $letter->letter_date?->format('d-m-Y') }}</td>
                <td>{{ $type === 'incoming' ? $letter->received_at?->format('d-m-Y') : $letter->sent_date?->format('d-m-Y') }}</td><td>{{ $letter->note }}</td>
            </tr>
        @empty
            <tr><td colspan="9">{{ __('reports.no_results') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
