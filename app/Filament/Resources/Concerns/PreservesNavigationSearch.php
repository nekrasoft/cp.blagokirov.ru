<?php

namespace App\Filament\Resources\Concerns;

use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;

use function Filament\Support\original_request;

trait PreservesNavigationSearch
{
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if (
            ($name === null || $name === '' || $name === 'index' || $name === 'edit')
            && ! array_key_exists('search', $parameters)
        ) {
            $parameters = array_merge($parameters, static::currentSearchQueryParameter());
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

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

        if ($search === null) {
            $search = static::searchQueryFromUrl(request()->header('Referer'));
        }

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

    protected static function searchQueryFromUrl(?string $url): mixed
    {
        if (! $url) {
            return null;
        }

        $queryString = parse_url($url, PHP_URL_QUERY);

        if (! is_string($queryString)) {
            return null;
        }

        parse_str($queryString, $query);

        return $query['search'] ?? null;
    }
}
