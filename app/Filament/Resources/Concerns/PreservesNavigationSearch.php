<?php

namespace App\Filament\Resources\Concerns;

use Filament\Navigation\NavigationItem;

use function Filament\Support\original_request;

trait PreservesNavigationSearch
{
    public static function getNavigationItems(): array
    {
        if (! static::hasPage('index')) {
            return [];
        }

        $activeRoutePattern = static::getNavigationItemActiveRoutePattern();

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => original_request()->routeIs($activeRoutePattern))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl())
                ->extraAttributes(['data-preserve-navigation-search' => 'true']),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('index', static::currentSearchQueryParameter());
    }

    protected static function currentSearchQueryParameter(): array
    {
        $search = request()->query('search');

        if (is_array($search)) {
            $search = reset($search);
        }

        $search = trim((string) $search);

        if ($search === '') {
            return [];
        }

        return [
            'search' => $search,
        ];
    }
}
