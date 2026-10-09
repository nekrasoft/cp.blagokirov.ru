@php
    $count = count($items);
    $quantity = round(array_sum(array_column($items, 'quantity')), 2);
    $volume = round(array_sum(array_column($items, 'volume')), 2);
    $format = fn ($value) => rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    $plural = function ($value, $one, $few, $many) {
        if ($value != floor($value)) {
            return $few;
        }
        $value = (int) $value;
        return $value % 100 >= 11 && $value % 100 <= 14 ? $many
            : ($value % 10 === 1 ? $one : ($value % 10 >= 2 && $value % 10 <= 4 ? $few : $many));
    };
@endphp

<div style="margin-bottom: .75rem;">
    Вывезено: <strong>{{ $count }} {{ $plural($count, 'бункер', 'бункера', 'бункеров') }}</strong>
    · К оплате: <strong>{{ $format($quantity) }} {{ $plural($quantity, 'единица', 'единицы', 'единиц') }}</strong>
    · Расчётный объём: <strong>{{ $format($volume) }} м³</strong>
</div>

<div class="pickup-items-table-wrap">
    <table class="pickup-items-table">
        <thead>
            <tr>
                <th>№ бункера</th>
                <th>Количество</th>
                <th>Расчётный объём</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>
                        @if (!empty($item['mapUrl']))
                            <a href="{{ $item['mapUrl'] }}" target="_blank" rel="noopener noreferrer" style="color: rgb(37 99 235); text-decoration: underline;">{{ $item['number'] }} ↗</a>
                        @else
                            {{ $item['number'] }}
                        @endif
                    </td>
                    <td>{{ number_format((float) $item['quantity'], 2, ',', ' ') }}</td>
                    <td>{{ number_format((float) $item['volume'], 2, ',', ' ') }} м³</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@once
    <style>
        .pickup-items-table-wrap { overflow-x: auto; }
        .pickup-items-table { width: 100%; border-collapse: collapse; }
        .pickup-items-table th, .pickup-items-table td { padding: .625rem 1rem; text-align: left; white-space: nowrap; }
        .pickup-items-table th { font-weight: 600; }
        .pickup-items-table tbody tr { border-top: 1px solid rgb(148 163 184 / .25); }
    </style>
@endonce
