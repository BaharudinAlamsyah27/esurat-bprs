<section class="esurat-greeting">
    @php($hour = now()->hour)
    <p>{{ __('app.greeting_' . ($hour < 11 ? 'morning' : ($hour < 15 ? 'afternoon' : ($hour < 19 ? 'evening' : 'night')))) }}, {{ auth()->user()?->name }}</p>
    <span>{{ __('app.dashboard_intro') }}</span>
</section>
