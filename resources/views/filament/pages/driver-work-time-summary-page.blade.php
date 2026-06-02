<x-filament-panels::page>
    <x-filament::section
        :heading="'Итоги за ' . $summary['month_label']"
        description="Сводка рабочего времени по каждому водителю для дальнейшего расчета зарплаты."
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

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-white/[0.04] dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Водитель</th>
                        <th scope="col" class="px-4 py-3">Источник</th>
                        <th scope="col" class="px-4 py-3">ID водителя</th>
                        <th scope="col" class="px-4 py-3 text-right">Рабочих дней</th>
                        <th scope="col" class="px-4 py-3 text-right">Записей</th>
                        <th scope="col" class="px-4 py-3 text-right">Суммарное время</th>
                        <th scope="col" class="px-4 py-3 text-right">Часы, десятичные</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-white/10 dark:text-gray-200">
                    @forelse ($summary['rows'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.04]">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                {{ $row['driver_name'] }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row['source'] }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row['driver_id'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $row['work_days'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $row['record_count'] }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                {{ $row['total_duration_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $row['total_hours_formatted'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Нет данных за выбранный месяц.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($summary['has_data'])
                    <tfoot class="bg-gray-100 font-bold text-gray-900 dark:bg-white/[0.06] dark:text-white">
                        <tr>
                            <td colspan="3" class="px-4 py-3">
                                {{ $summary['totals']['driver_name'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $summary['totals']['work_days'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $summary['totals']['record_count'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $summary['totals']['total_duration_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $summary['totals']['total_hours_formatted'] }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
