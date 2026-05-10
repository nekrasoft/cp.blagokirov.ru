<?php

namespace App\Support;

use App\Http\Middleware\UseCounterpartyDemoDatabase;
use App\Models\CounterpartyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DemoDatabaseDebug
{
    private const TABLES = [
        'counterparties',
        'counterparty_users',
        'bunkers',
        'bunker_fill_requests',
        'invoices',
        'works',
    ];

    public static function enabled(?Request $request = null): bool
    {
        $request ??= request();

        if (! $request->hasSession()) {
            return false;
        }

        return (bool) $request->session()->get(UseCounterpartyDemoDatabase::SESSION_KEY, false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(?Request $request = null): array
    {
        $request ??= request();

        $connectionName = DB::getDefaultConnection();
        $connection = DB::connection($connectionName);
        $config = config("database.connections.{$connectionName}", []);
        $user = self::counterpartyUser($request);

        return [
            'enabled' => self::enabled($request),
            'session_marker' => $request->hasSession()
                ? (bool) $request->session()->get(UseCounterpartyDemoDatabase::SESSION_KEY, false)
                : null,
            'request' => [
                'path' => '/' . ltrim($request->path(), '/'),
                'route' => optional($request->route())->getName(),
            ],
            'auth' => [
                'id' => $user?->getAuthIdentifier(),
                'login' => $user?->login,
                'counterparty_id' => $user?->counterparty_id,
                'is_demo' => $user?->is_demo,
                'is_active' => $user?->is_active,
            ],
            'connection' => [
                'default' => $connectionName,
                'demo_config_name' => (string) config('database.demo_connection', 'demo'),
                'driver' => $connection->getDriverName(),
                'configured_database' => $config['database'] ?? null,
                'actual_database' => self::actualDatabaseName($connectionName),
            ],
            'tables' => self::tables($connectionName, $user),
        ];
    }

    public static function headerValue(?Request $request = null): string
    {
        return base64_encode((string) json_encode(self::snapshot($request), JSON_UNESCAPED_UNICODE));
    }

    private static function counterpartyUser(Request $request): ?CounterpartyUser
    {
        $user = $request->user('counterparty');

        return $user instanceof CounterpartyUser ? $user : null;
    }

    private static function actualDatabaseName(string $connectionName): ?string
    {
        try {
            $driver = DB::connection($connectionName)->getDriverName();

            return match ($driver) {
                'mysql', 'mariadb' => (string) DB::connection($connectionName)->selectOne('select database() as name')->name,
                'pgsql' => (string) DB::connection($connectionName)->selectOne('select current_database() as name')->name,
                'sqlsrv' => (string) DB::connection($connectionName)->selectOne('select DB_NAME() as name')->name,
                'sqlite' => self::sqliteDatabaseName($connectionName),
                default => null,
            };
        } catch (Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private static function sqliteDatabaseName(string $connectionName): ?string
    {
        $rows = DB::connection($connectionName)->select('pragma database_list');
        $main = collect($rows)->first(fn (object $row): bool => ($row->name ?? null) === 'main');

        return $main?->file ?: ':memory:';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function tables(string $connectionName, ?CounterpartyUser $user): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            $tables[$table] = self::table($connectionName, $table, $user);
        }

        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    private static function table(string $connectionName, string $table, ?CounterpartyUser $user): array
    {
        try {
            $schema = Schema::connection($connectionName);
            $exists = $schema->hasTable($table);

            if (! $exists) {
                return [
                    'exists' => false,
                    'count' => null,
                    'has_counterparty_id' => false,
                    'counterparty_rows' => null,
                    'error' => null,
                ];
            }

            $query = DB::connection($connectionName)->table($table);
            $hasCounterpartyId = $schema->hasColumn($table, 'counterparty_id');

            return [
                'exists' => true,
                'count' => self::safeCount($query),
                'has_counterparty_id' => $hasCounterpartyId,
                'counterparty_rows' => $hasCounterpartyId && $user
                    ? self::safeCount(DB::connection($connectionName)->table($table)->where('counterparty_id', (int) $user->counterparty_id))
                    : null,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'exists' => null,
                'count' => null,
                'has_counterparty_id' => null,
                'counterparty_rows' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private static function safeCount(object $query): ?int
    {
        try {
            return (int) $query->count();
        } catch (Throwable) {
            return null;
        }
    }
}
