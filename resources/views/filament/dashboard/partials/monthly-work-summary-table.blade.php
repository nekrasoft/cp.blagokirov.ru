@once
    <style>
        .blago-work-summary {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e4e7ec;
            border-radius: 8px;
        }

        .blago-work-summary__table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            font-size: 14px;
            line-height: 1.35;
            color: #101828;
        }

        .blago-work-summary__table th,
        .blago-work-summary__table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #e4e7ec;
        }

        .blago-work-summary__table th {
            background: #f9fafb;
            color: #667085;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: left;
            white-space: nowrap;
        }

        .blago-work-summary__table tbody tr:nth-child(even) td {
            background: #fcfcfd;
        }

        .blago-work-summary__table tbody tr:hover td {
            background: #f9fafb;
        }

        .blago-work-summary__name {
            font-weight: 600;
            color: #101828;
            overflow-wrap: anywhere;
        }

        .blago-work-summary__number {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            color: #344054;
        }

        .blago-work-summary__table th.blago-work-summary__number {
            text-align: right;
        }

        .blago-work-summary__table tfoot td {
            background: #f2f4f7;
            border-top: 1px solid #d0d5dd;
            border-bottom: 0;
            font-weight: 700;
            color: #101828;
        }

        .blago-work-summary__empty {
            padding: 32px 16px;
            text-align: center;
            color: #667085;
        }

        .dark .blago-work-summary__table {
            color: #f9fafb;
        }

        .dark .blago-work-summary {
            border-color: rgb(255 255 255 / 0.1);
        }

        .dark .blago-work-summary__table th {
            background: rgb(255 255 255 / 0.04);
            color: #98a2b3;
        }

        .dark .blago-work-summary__table th,
        .dark .blago-work-summary__table td {
            border-bottom-color: rgb(255 255 255 / 0.1);
        }

        .dark .blago-work-summary__table tbody tr:nth-child(even) td {
            background: rgb(255 255 255 / 0.02);
        }

        .dark .blago-work-summary__table tbody tr:hover td {
            background: rgb(255 255 255 / 0.04);
        }

        .dark .blago-work-summary__name,
        .dark .blago-work-summary__table tfoot td {
            color: #ffffff;
        }

        .dark .blago-work-summary__number {
            color: #d0d5dd;
        }

        .dark .blago-work-summary__table tfoot td {
            background: rgb(255 255 255 / 0.06);
            border-top-color: rgb(255 255 255 / 0.16);
        }

        @media (max-width: 640px) {
            .blago-work-summary__table {
                min-width: 680px;
                font-size: 13px;
            }

            .blago-work-summary__table th,
            .blago-work-summary__table td {
                padding: 10px 12px;
            }
        }
    </style>
@endonce

<div class="blago-work-summary">
    <table class="blago-work-summary__table">
        <colgroup>
            <col style="width: 34%">
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 21%">
            <col style="width: 21%">
        </colgroup>

        <thead>
            <tr>
                <th scope="col">Наименование</th>
                <th scope="col" class="blago-work-summary__number">Кол-во, шт</th>
                <th scope="col" class="blago-work-summary__number">Объем, м3</th>
                <th scope="col" class="blago-work-summary__number">Выручка</th>
                <th scope="col" class="blago-work-summary__number">Поступило</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($summary['rows'] as $row)
                <tr>
                    <td class="blago-work-summary__name">
                        {{ $row['name'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $row['quantity_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $row['volume_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $row['revenue_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $row['received_formatted'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="blago-work-summary__empty">
                        Нет данных за выбранный месяц.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if ($summary['has_data'])
            <tfoot>
                <tr>
                    <td class="blago-work-summary__name">
                        {{ $summary['totals']['name'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $summary['totals']['quantity_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $summary['totals']['volume_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $summary['totals']['revenue_formatted'] }}
                    </td>
                    <td class="blago-work-summary__number">
                        {{ $summary['totals']['received_formatted'] }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
