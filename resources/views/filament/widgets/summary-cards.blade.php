<x-filament-widgets::widget class="esurat-summary-grid">
    @foreach ($this->getCards() as $card)
        <a class="esurat-summary-card" href="{{ $card['url'] }}">
            <strong>{{ $card['value'] }}</strong>
            <span>{{ $card['label'] }}</span>
        </a>
    @endforeach
</x-filament-widgets::widget>
