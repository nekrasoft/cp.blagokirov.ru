<?php

namespace App\Filament\Support;

use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;

final class TailAdminTheme
{
    private const BRAND = [
        50 => '#ecf3ff',
        100 => '#dde9ff',
        200 => '#c2d6ff',
        300 => '#9cb9ff',
        400 => '#7592ff',
        500 => '#465fff',
        600 => '#3641f5',
        700 => '#2a31d8',
        800 => '#252dae',
        900 => '#262e89',
        950 => '#161950',
    ];

    public static function configure(Panel $panel): Panel
    {
        return $panel
            ->viteTheme('resources/css/filament/tailadmin/theme.css')
            ->colors([
                'primary' => self::BRAND,
                'gray' => Color::Gray,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18.125rem')
            ->collapsedSidebarWidth('5.625rem')
            ->maxContentWidth(Width::ScreenTwoExtraLarge);
    }
}
