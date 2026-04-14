<?php

namespace App\Providers\Filament;

use App\Filament\Auth\CounterpartyLogin;
use App\Filament\Resources\WorkResource;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CounterpartyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('counterparty')
            ->path('billing')
            ->login(CounterpartyLogin::class)
            ->authGuard('counterparty')
            ->homeUrl(fn (): string => WorkResource::getUrl(panel: 'counterparty'))
            ->brandName(function (): string {
                $user = Filament::auth()->user();
                $counterpartyName = trim((string) ($user?->counterparty?->name ?? ''));

                if ($counterpartyName === '') {
                    return 'Биллинг';
                }

                return 'Биллинг — ' . $counterpartyName;
            })
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->navigationItems([
                NavigationItem::make('Карта бункеров ↗')
                    ->icon(Heroicon::OutlinedMap)
                    ->url(fn (): string => route('billing.sso.map'), true)
                    ->sort(999),
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
            ]);
    }
}
