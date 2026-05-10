<?php

namespace Tests\Unit;

use App\Http\Middleware\UseCounterpartyDemoDatabase;
use App\Models\CounterpartyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $request->setUserResolver(fn (?string $guard = null): ?CounterpartyUser => $guard === 'counterparty'
            ? new CounterpartyUser(['is_demo' => true])
            : null);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertSame('demo', DB::getDefaultConnection());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_keeps_primary_connection_for_regular_counterparty_user(): void
    {
        DB::setDefaultConnection('sqlite');

        $request = Request::create('/billing');
        $request->setUserResolver(fn (?string $guard = null): ?CounterpartyUser => $guard === 'counterparty'
            ? new CounterpartyUser(['is_demo' => false])
            : null);

        $middleware = new UseCounterpartyDemoDatabase();

        $response = $middleware->handle($request, function (): Response {
            $this->assertSame('sqlite', DB::getDefaultConnection());

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }
}
