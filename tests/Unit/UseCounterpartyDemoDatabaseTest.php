<?php

namespace Tests\Unit;

use App\Http\Middleware\UseCounterpartyDemoDatabase;
use App\Filament\Resources\BunkerResource;
use App\Filament\Support\RuntimeSchemaCache;
use App\Models\CounterpartyUser;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UseCounterpartyDemoDatabaseTest extends TestCase
{
    public function test_it_switches_to_demo_connection_for_demo_counterparty_user(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.demo_connection', 'demo');
        config()->set('database.connections.demo', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::setDefaultConnection('sqlite');

        $request = Request::create('/billing');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
        $request->setUserResolver(fn (?string $guard = null): ?CounterpartyUser => $guard === 'counterparty'
            ? new CounterpartyUser(['is_demo' => true])
            : null);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertSame('demo', DB::getDefaultConnection());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($request->session()->get(UseCounterpartyDemoDatabase::SESSION_KEY));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_switches_to_demo_connection_from_session_marker(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.demo_connection', 'demo');
        config()->set('database.connections.demo', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::setDefaultConnection('sqlite');

        $request = Request::create('/billing');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
        $request->session()->put(UseCounterpartyDemoDatabase::SESSION_KEY, true);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertSame('demo', DB::getDefaultConnection());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_flushes_resource_schema_cache_after_switching_to_demo_connection(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.demo_connection', 'demo');
        config()->set('database.connections.demo', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('demo');
        DB::setDefaultConnection('sqlite');
        RuntimeSchemaCache::flush();

        $this->assertFalse($this->bunkerResourceHasTable());

        DB::connection('demo')->getSchemaBuilder()->create('bunkers', function ($table): void {
            $table->string('id')->primary();
        });

        $request = Request::create('/billing');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
        $request->session()->put(UseCounterpartyDemoDatabase::SESSION_KEY, true);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertTrue($this->bunkerResourceHasTable());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_keeps_primary_connection_for_regular_counterparty_user(): void
    {
        DB::setDefaultConnection('sqlite');

        $request = Request::create('/billing');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
        $request->setUserResolver(fn (?string $guard = null): ?CounterpartyUser => $guard === 'counterparty'
            ? new CounterpartyUser(['is_demo' => false])
            : null);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertSame('sqlite', DB::getDefaultConnection());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertFalse($request->session()->get(UseCounterpartyDemoDatabase::SESSION_KEY));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    private function bunkerResourceHasTable(): bool
    {
        $method = new ReflectionMethod(BunkerResource::class, 'hasTable');
        $method->setAccessible(true);

        return (bool) $method->invoke(null);
    }
}
