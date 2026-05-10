<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo DB debug</title>
    <style>
        body { margin: 0; padding: 24px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #111827; background: #f8fafc; }
        h1 { margin: 0 0 16px; font-size: 22px; }
        pre { overflow: auto; padding: 16px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; font-size: 13px; line-height: 1.45; }
    </style>
</head>
<body>
    <h1>Demo DB debug</h1>
    <pre>{{ json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
