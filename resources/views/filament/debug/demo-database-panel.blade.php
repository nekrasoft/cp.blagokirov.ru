@php
    use App\Support\DemoDatabaseDebug;

    $debug = DemoDatabaseDebug::enabled() ? DemoDatabaseDebug::snapshot() : null;
@endphp

@if ($debug)
    <div style="position: fixed; right: 16px; bottom: 16px; z-index: 9999; width: min(560px, calc(100vw - 32px)); max-height: 50vh; overflow: auto; border: 1px solid #f59e0b; border-radius: 8px; background: #fffbeb; color: #111827; box-shadow: 0 12px 24px rgba(15, 23, 42, .18); font: 12px/1.45 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
        <details open>
            <summary style="cursor: pointer; padding: 10px 12px; font-weight: 700; background: #fef3c7;">
                Demo DB debug: {{ $debug['connection']['default'] ?? '?' }} / {{ $debug['connection']['actual_database'] ?? '?' }}
            </summary>
            <pre style="white-space: pre-wrap; margin: 0; padding: 12px;">{{ json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    </div>
@endif
