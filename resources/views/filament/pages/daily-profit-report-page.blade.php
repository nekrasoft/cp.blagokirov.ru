<x-filament-panels::page>
    <x-filament::section
        heading="Отчет по прибыли"
        description="Выручка по работам минус распределенные расходы на ГСМ и утилизацию."
    >
        <form method="get" class="mb-5 grid gap-4 md:grid-cols-[minmax(0,12rem)_minmax(0,12rem)_minmax(0,12rem)_auto] md:items-end">
            <div>
                <label for="date_from" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    С даты
                </label>

                <x-filament::input.wrapper>
                    <x-filament::input
                        id="date_from"
                        name="date_from"
                        type="date"
                        value="{{ $dateFrom }}"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="date_to" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    По дату
                </label>

                <x-filament::input.wrapper>
                    <x-filament::input
                        id="date_to"
                        name="date_to"
                        type="date"
                        value="{{ $dateTo }}"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="group_by" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Группировка
                </label>

                <x-filament::input.wrapper>
                    <x-filament::input.select id="group_by" name="group_by">
                        @foreach ($groupOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === $groupBy)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Показать
            </x-filament::button>
        </form>

        <div class="mb-5 grid gap-4 border-y border-gray-200 py-4 md:grid-cols-5 dark:border-white/10">
            <div>
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Выручка</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $report['totals']['revenue_formatted'] }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">ГСМ</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $report['totals']['fuel_expense_formatted'] }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Полигоны</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $report['totals']['landfill_expense_formatted'] }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Расходы</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $report['totals']['total_expense_formatted'] }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Прибыль</div>
                <div @class([
                    'mt-1 text-lg font-semibold',
                    'text-success-600 dark:text-success-400' => $report['totals']['profit'] >= 0,
                    'text-danger-600 dark:text-danger-400' => $report['totals']['profit'] < 0,
                ])>
                    {{ $report['totals']['profit_formatted'] }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-white/[0.04] dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Период</th>
                        <th scope="col" class="px-4 py-3 text-right">Выручка</th>
                        <th scope="col" class="px-4 py-3 text-right">ГСМ</th>
                        <th scope="col" class="px-4 py-3 text-right">Полигоны</th>
                        <th scope="col" class="px-4 py-3 text-right">Расходы</th>
                        <th scope="col" class="px-4 py-3 text-right">Прибыль</th>
                        @if ($report['group_by'] === 'month')
                            <th scope="col" class="px-4 py-3 text-right">Рабочие дни</th>
                            <th scope="col" class="px-4 py-3 text-right">Прибыль/день</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-white/10 dark:text-gray-200">
                    @forelse ($report['rows'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.04]">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                {{ $row['label'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['revenue_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['fuel_expense_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['landfill_expense_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['total_expense_formatted'] }}</td>
                            <td @class([
                                'px-4 py-3 text-right font-semibold tabular-nums',
                                'text-success-600 dark:text-success-400' => $row['profit'] >= 0,
                                'text-danger-600 dark:text-danger-400' => $row['profit'] < 0,
                            ])>
                                {{ $row['profit_formatted'] }}
                            </td>
                            @if ($report['group_by'] === 'month')
                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['work_days'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $row['avg_profit_per_work_day_formatted'] }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $report['group_by'] === 'month' ? 8 : 6 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Нет данных за выбранный период.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($report['rows'] !== [])
                    <tfoot class="bg-gray-100 font-bold text-gray-900 dark:bg-white/[0.06] dark:text-white">
                        <tr>
                            <td class="px-4 py-3">{{ $report['totals']['label'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['revenue_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['fuel_expense_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['landfill_expense_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['total_expense_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['profit_formatted'] }}</td>
                            @if ($report['group_by'] === 'month')
                                <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['work_days'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $report['totals']['avg_profit_per_work_day_formatted'] }}</td>
                            @endif
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
