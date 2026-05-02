@if (filament()->auth()->check() && filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <div class="fi-ta-theme-toggle" x-data>
        <x-filament::icon-button
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::Moon"
            :icon-alias="\Filament\View\PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON"
            icon-size="lg"
            :label="__('filament-panels::layout.actions.theme_switcher.dark.label')"
            :tooltip="__('filament-panels::layout.actions.theme_switcher.dark.label')"
            x-cloak
            x-show="$store.theme !== 'dark'"
            x-on:click="$dispatch('theme-changed', 'dark')"
            class="fi-ta-theme-toggle-btn"
        />

        <x-filament::icon-button
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::Sun"
            :icon-alias="\Filament\View\PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON"
            icon-size="lg"
            :label="__('filament-panels::layout.actions.theme_switcher.light.label')"
            :tooltip="__('filament-panels::layout.actions.theme_switcher.light.label')"
            x-cloak
            x-show="$store.theme === 'dark'"
            x-on:click="$dispatch('theme-changed', 'light')"
            class="fi-ta-theme-toggle-btn"
        />
    </div>
@endif
