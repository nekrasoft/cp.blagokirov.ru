<?php

namespace App\Http\Middleware;

use App\Models\CounterpartyUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UseCounterpartyDemoDatabase
{
    public const SESSION_KEY = 'counterparty_uses_demo_database';

    public function handle(Request $request, Closure $next): Response
    {
        $usesDemoDatabase = (bool) $request->session()->get(self::SESSION_KEY, false);

        if (! $usesDemoDatabase) {
            $user = $request->user('counterparty');
            $usesDemoDatabase = $user instanceof CounterpartyUser && $user->is_demo;

            if ($user instanceof CounterpartyUser) {
                $request->session()->put(self::SESSION_KEY, $usesDemoDatabase);
            }
        }

        if (! $usesDemoDatabase) {
            return $next($request);
        }

        $connection = (string) config('database.demo_connection', 'demo');
        $connectionConfig = config("database.connections.{$connection}");

        if ($connection === '' || ! is_array($connectionConfig) || blank($connectionConfig['database'] ?? null)) {
            abort(503, 'Demo database connection is not configured.');
        }

        $previousConnection = DB::getDefaultConnection();
        DB::setDefaultConnection($connection);

        try {
            return $next($request);
        } finally {
            DB::setDefaultConnection($previousConnection);
        }
    }
}
