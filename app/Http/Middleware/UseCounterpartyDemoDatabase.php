<?php

namespace App\Http\Middleware;

use App\Models\CounterpartyUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UseCounterpartyDemoDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('counterparty');

        if (! $user instanceof CounterpartyUser || ! $user->is_demo) {
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
