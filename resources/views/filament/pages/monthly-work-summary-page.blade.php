<x-filament-panels::page>
    <x-filament::section
        :heading="'Итоги за ' . $summary['month_label']"
        description="Историческая сводка по количеству, объему, выручке и поступлениям."
    >
        <form method="get" class="mb-5 max-w-xs">
            <label for="month" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                Месяц
            </label>

            <x-filament::input.wrapper>
                <x-filament::input.select
                    id="month"
                    name="month"
                    onchange="this.form.submit()"
                >
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $selectedMonth)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </form>

        @include('filament.dashboard.partials.monthly-work-summary-table', ['summary' => $summary])
    </x-filament::section>
</x-filament-panels::page>
