@php
    $brandName = trim(strip_tags(filament()->getBrandName()));
@endphp

<div class="fi-ta-logo">
    <span class="fi-ta-logo-mark" aria-hidden="true">Б</span>
    <span class="fi-logo fi-ta-logo-text">
        {{ $brandName !== '' ? $brandName : config('app.name') }}
    </span>
</div>
