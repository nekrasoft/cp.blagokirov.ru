<x-filament-widgets::widget>
    <x-filament::section
        :heading="'Итоги за ' . $summary['month_label']"
        description="Выручка и фактические поступления по категориям работ."
    >
        <div class="mb-4 flex justify-end">
            <x-filament::button
                tag="a"
                :href="$reportUrl"
                color="gray"
                size="sm"
                icon="heroicon-m-calendar-days"
            >
                История
            </x-filament::button>
        </div>

        @include('filament.dashboard.partials.monthly-work-summary-table', ['summary' => $summary])
    </x-filament::section>
</x-filament-widgets::widget>
