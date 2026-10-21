@php($letters = $this->getLetters())
<x-filament-widgets::widget class="esurat-latest-letters">
    <h2>{{ __('app.latest_letters') }}</h2>
    <div class="esurat-latest-grid">
        @foreach (['incoming' => __('incoming.plural'), 'outgoing' => __('outgoing.plural')] as $type => $label)
            <article class="esurat-latest-panel">
                <h3>{{ $label }}</h3>
                @forelse ($letters[$type] as $letter)
                    <a class="esurat-latest-row" href="{{ $letter['url'] }}">
                        <span class="esurat-latest-date">{{ $letter['date'] }}</span>
                        <span class="esurat-latest-main">
                            <strong>{{ $letter['subject'] }}</strong>
                            <small>{{ $letter['number'] }} · {{ $type === 'incoming' ? $letter['sender'] : $letter['recipient'] }}</small>
                        </span>
                        <span class="esurat-latest-link">{{ __('app.view') }}</span>
                    </a>
                @empty
                    <p class="esurat-empty">{{ __('app.no_data') }}</p>
                @endforelse
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
