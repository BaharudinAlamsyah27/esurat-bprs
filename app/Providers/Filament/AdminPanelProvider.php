<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Profile;
use App\Filament\Support\LocalFontProvider;
use App\Filament\Widgets\FontScaleWidget;
use App\Filament\Widgets\GreetingWidget;
use App\Filament\Widgets\SummaryCardsWidget;
use App\Http\Middleware\IdleSessionTimeout;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->brandName('E-Surat BPRS Sragen')
            ->darkMode(false)
            ->font('Plus Jakarta Sans', provider: LocalFontProvider::class)
            ->colors([
                'primary' => Color::hex('#0F5C46'),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('m1.profile'))
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->sidebarCollapsibleOnDesktop(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                'DATA MASTER',
                'SURAT',
                'LAPORAN',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                Profile::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                GreetingWidget::class,
                SummaryCardsWidget::class,
                FontScaleWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                IdleSessionTimeout::class,
            ]);
    }
}
