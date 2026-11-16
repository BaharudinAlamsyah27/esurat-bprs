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
        <thead>
            <tr>
                <th>{{ __('reports.number') }}</th>
                <th>{{ __('cooperation.bss_pks_number') }}</th>
                <th>{{ __('cooperation.partner_pks_number') }}</th>
                <th>{{ __('cooperation.partner_name') }}</th>
                <th>{{ __('cooperation.partner_npwp') }}</th>
                <th>{{ __('cooperation.agreement_date') }}</th>
                <th>{{ __('cooperation.subject') }}</th>
                <th>{{ __('cooperation.pic_name') }}</th>
                <th>{{ __('cooperation.note') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($agreements as $index => $agreement)
            <tr>
                <td class="number">{{ $index + 1 }}</td>
                <td>{{ $agreement->bss_pks_number }}</td>
                <td>{{ $agreement->partner_pks_number ?? '—' }}</td>
                <td>{{ $agreement->partner_name }}</td>
                <td>{{ $agreement->partner_npwp ?? '—' }}</td>
                <td>{{ $agreement->agreement_date->format('d-m-Y') }}</td>
                <td>{{ $agreement->subject }}</td>
                <td>{{ $agreement->pic_name }}</td>
                <td>{{ $agreement->note ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="9">{{ __('reports.cooperation_no_results') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
