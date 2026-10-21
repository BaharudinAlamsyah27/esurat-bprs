<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return __('app.dashboard');
    }

    public function getSubheading(): string
    {
        $hour = now()->hour;
        $greeting = __('app.greeting_'.($hour < 11 ? 'morning' : ($hour < 15 ? 'afternoon' : ($hour < 19 ? 'evening' : 'night'))));

        return $greeting.', '.auth()->user()?->name.' — '.__('app.dashboard_intro');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.dashboard');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label(__('app.help'))
                ->icon('heroicon-o-question-mark-circle')
                ->modalHeading(__('app.help_dashboard_title'))
                ->modalDescription(__('app.help_dashboard_body'))
                ->modalSubmitActionLabel(__('app.close')),
        ];
    }
}
