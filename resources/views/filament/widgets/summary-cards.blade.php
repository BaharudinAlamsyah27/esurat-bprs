<div class="esurat-summary-grid">
    @foreach ($this->getCards() as $card)
        <article class="esurat-summary-card">
            <strong>{{ $card['value'] }}</strong>
            <span>{{ $card['label'] }}</span>
        </article>
    @endforeach
</div>
