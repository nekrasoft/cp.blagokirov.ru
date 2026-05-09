<div class="overflow-x-auto">
    <table class="w-full min-w-[720px] divide-y divide-gray-200 text-sm dark:divide-white/10">
        <thead>
            <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                <th scope="col" class="px-4 py-3">Наименование</th>
                <th scope="col" class="px-4 py-3 text-right">Кол-во, шт</th>
                <th scope="col" class="px-4 py-3 text-right">Объем, м3</th>
                <th scope="col" class="px-4 py-3 text-right">Выручка</th>
                <th scope="col" class="px-4 py-3 text-right">Поступило</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($summary['rows'] as $row)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                        {{ $row['name'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200">
                        {{ $row['quantity_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200">
                        {{ $row['volume_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200">
                        {{ $row['revenue_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200">
                        {{ $row['received_formatted'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        Нет данных за выбранный месяц.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if ($summary['has_data'])
            <tfoot>
                <tr class="bg-gray-50 font-semibold text-gray-950 dark:bg-white/5 dark:text-white">
                    <td class="px-4 py-3">
                        {{ $summary['totals']['name'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        {{ $summary['totals']['quantity_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        {{ $summary['totals']['volume_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        {{ $summary['totals']['revenue_formatted'] }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        {{ $summary['totals']['received_formatted'] }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
