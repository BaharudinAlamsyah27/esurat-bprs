<section class="esurat-font-control" x-data="{
    scale: localStorage.getItem('esurat-font-scale') ?? 'normal',
    apply(value) {
        this.scale = value;
        localStorage.setItem('esurat-font-scale', value);
        document.documentElement.classList.remove('esurat-font-large', 'esurat-font-largest');
        if (value === 'large') document.documentElement.classList.add('esurat-font-large');
        if (value === 'largest') document.documentElement.classList.add('esurat-font-largest');
    }
}" x-init="apply(scale)">
    <label for="font-scale">{{ __('app.font_size') }}</label>
    <select id="font-scale" x-model="scale" @change="apply($event.target.value)">
        <option value="normal">{{ __('app.font_normal') }}</option>
        <option value="large">{{ __('app.font_large') }}</option>
        <option value="largest">{{ __('app.font_largest') }}</option>
    </select>
</section>
