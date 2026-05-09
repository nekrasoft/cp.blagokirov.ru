<x-filament-widgets::widget>
    @once
        <style>
            .blago-work-summary-toolbar {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 16px;
            }
        </style>
    @endonce

    <x-filament::section
        :heading="'Итоги за ' . $summary['month_label']"
        description="Выручка и фактические поступления по категориям работ."
    >
        <div class="blago-work-summary-toolbar">
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
