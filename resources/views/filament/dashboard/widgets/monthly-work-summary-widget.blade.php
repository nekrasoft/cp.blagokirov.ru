<x-filament-widgets::widget>
    <x-filament::section
        :heading="'Итоги за ' . $summary['month_label']"
        description="Выручка и фактические поступления по категориям работ."
    >
        <x-slot name="afterHeader">
            <x-filament::button
                tag="a"
                :href="$reportUrl"
                color="gray"
                size="sm"
                icon="heroicon-m-calendar-days"
            >
                История
            </x-filament::button>
        </x-slot>

        @include('filament.dashboard.partials.monthly-work-summary-table', ['summary' => $summary])
    </x-filament::section>
</x-filament-widgets::widget>
