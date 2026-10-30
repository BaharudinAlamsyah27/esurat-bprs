<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Profile;
use App\Filament\Support\LocalFontProvider;
use App\Filament\Support\ThemePalette;
use App\Filament\Widgets\FontScaleWidget;
use App\Filament\Widgets\LatestLettersWidget;
use App\Filament\Widgets\LettersByClassificationChart;
use App\Filament\Widgets\LettersByMonthChart;
use App\Filament\Widgets\SummaryCardsWidget;
use App\Http\Middleware\IdleSessionTimeout;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
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
            ->brandLogo(asset('images/bprs-sragen.png'))
            ->brandLogoHeight('2.5rem')
            ->darkMode()
            ->defaultThemeMode(ThemeMode::Light)
            ->font('Plus Jakarta Sans', provider: LocalFontProvider::class)
            ->colors(fn (): array => ThemePalette::filamentColors())
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): HtmlString => new HtmlString(
                ThemePalette::styles().
                '<meta name="apple-mobile-web-app-capable" content="yes">'.
                '<meta name="mobile-web-app-capable" content="yes">'.
                '<meta name="apple-mobile-web-app-status-bar-style" content="default">'.
                '<meta name="apple-mobile-web-app-title" content="E-Surat">'.
                '<link rel="apple-touch-icon" href="'.asset('images/bprs-sragen.png').'">'.
                '<link rel="manifest" href="'.asset('manifest.json').'">'
            ))
            ->renderHook(PanelsRenderHook::BODY_END, function (): HtmlString {
                /** @var Vite $vite */
                $vite = app(Vite::class);

                // Memuat mobile-bridge.js agar APK Android bisa mendaftarkan token
                // notifikasi dan menangani tombol kembali.
                return $vite(['resources/js/app.js']);
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('m1.profile'))
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
                SummaryCardsWidget::class,
                LettersByMonthChart::class,
                LettersByClassificationChart::class,
                LatestLettersWidget::class,
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
