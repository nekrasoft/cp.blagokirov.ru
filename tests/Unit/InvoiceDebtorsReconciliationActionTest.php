<?php

namespace Tests\Unit;

use App\Filament\Resources\InvoiceResource\Pages\ListInvoiceDebtors;
use App\Models\Counterparty;
use App\Models\CounterpartyUser;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BitrixReconciliationTaskService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InvoiceDebtorsReconciliationActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Http::preventStrayRequests();
    }

    public function test_admin_action_creates_task_for_counterparty_in_grouped_row(): void
    {
        $this->actingAs(new User(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        $counterparty = new Counterparty(['id' => 42, 'name' => 'Альфа']);
        $record = new Invoice(['id' => 42, 'counterparty_id' => 42]);
        $record->setRelation('counterparty', $counterparty);
        $this->mock(BitrixReconciliationTaskService::class)
            ->shouldReceive('create')->once()->with($counterparty)->andReturn(123);

        $action = $this->action()->record($record);
        $this->assertTrue($action->isVisible());
        $action->call(['record' => $record]);
    }

    public function test_readonly_admin_cannot_see_or_execute_action(): void
    {
        $this->actingAs(new User(['role' => User::ROLE_READONLY_ADMIN, 'is_active' => true]));
        $action = $this->action();

        $this->assertFalse($action->isVisible());
        $this->expectException(HttpException::class);
        $action->call(['record' => new Invoice(['counterparty_id' => 42])]);
    }

    public function test_customer_cannot_see_or_execute_action(): void
    {
        $this->actingAs(new CounterpartyUser(['counterparty_id' => 42, 'is_active' => true]));
        $action = $this->action();

        $this->assertFalse($action->isVisible());
        $this->expectException(HttpException::class);
        $action->call(['record' => new Invoice(['counterparty_id' => 42])]);
    }

    private function action(): Action
    {
        $page = new ListInvoiceDebtors;

        return $page->table(Table::make($page))->getAction('requestReconciliation');
    }
}
