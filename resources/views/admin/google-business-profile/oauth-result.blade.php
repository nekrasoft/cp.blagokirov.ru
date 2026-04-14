<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Business Profile OAuth</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; color: #111827; background: #f9fafb; }
        .wrap { max-width: 860px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
        .ok { border-color: #10b981; }
        .fail { border-color: #ef4444; }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        p { margin: .5rem 0; }
        textarea, pre { width: 100%; box-sizing: border-box; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9rem; }
        textarea { min-height: 110px; padding: .75rem; border: 1px solid #d1d5db; border-radius: 10px; background: #f9fafb; }
        pre { white-space: pre-wrap; word-break: break-word; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: .75rem; }
        .hint { color: #4b5563; font-size: .92rem; }
        code { background: #f3f4f6; padding: .1rem .35rem; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card {{ $success ? 'ok' : 'fail' }}">
            <h1>Google Business Profile OAuth</h1>
            <p>{{ $message }}</p>

            @if ($success && filled($refreshToken))
                <p><strong>Добавьте в <code>.env</code>:</strong></p>
                <textarea readonly>GOOGLE_BUSINESS_PROFILE_REFRESH_TOKEN={{ $refreshToken }}</textarea>
            @endif

            @if (filled($accessToken))
                <p class="hint">Также получен access token (временный):</p>
                <textarea readonly>{{ $accessToken }}</textarea>
            @endif

            @if (filled($rawResponse))
                <p class="hint">Ответ Google:</p>
                <pre>{{ json_encode($rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    </div>
</body>
</html>

