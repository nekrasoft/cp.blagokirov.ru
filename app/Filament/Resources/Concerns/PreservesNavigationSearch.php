<?php

namespace App\Filament\Resources\Concerns;

trait PreservesNavigationSearch
{
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
