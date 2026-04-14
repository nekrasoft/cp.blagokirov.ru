@php
    $count = (int) ($unpaidInvoicesCount ?? 0);
    $mod100 = $count % 100;
    $mod10 = $count % 10;
    $word = 'неоплаченных счетов';

    if ($mod100 < 11 || $mod100 > 14) {
        if ($mod10 === 1) {
            $word = 'неоплаченный счёт';
        } elseif ($mod10 >= 2 && $mod10 <= 4) {
            $word = 'неоплаченных счёта';
        }
    }

    $description = "У вас {$count} {$word}. Проверьте счета и свяжитесь с бухгалтерией, чтобы закрыть задолженность.";
@endphp

<x-filament::callout
    color="danger"
    heading="У вас есть неоплаченные счета"
    icon="heroicon-m-exclamation-triangle"
    :description="$description"
/>
