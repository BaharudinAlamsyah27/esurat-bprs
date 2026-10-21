<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Validation\Rule;

class FontScaleWidget extends Widget
{
    protected static string $view = 'filament.widgets.font-scale';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function getFontScale(): string
    {
        return (string) (auth()->user()->ui_font_scale ?? 'normal');
    }

    public function updateFontScale(string $fontScale): void
    {
        validator(['font_scale' => $fontScale], [
            'font_scale' => [Rule::in(['normal', 'large', 'largest'])],
        ])->validate();

        /** @var User|null $user */
        $user = auth()->user();

        $user?->forceFill(['ui_font_scale' => $fontScale])->save();
    }
}
