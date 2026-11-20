<?php

namespace Tests\Unit;

use App\Models\Counterparty;
use App\Services\BitrixReconciliationTaskService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class BitrixReconciliationTaskServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.bitrix24.task_webhook_url' => 'https://example.test/rest/1/secret/']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_creates_task_with_invoice_assignment_and_counterparty_details(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 15:30:00', 'Europe/Moscow'));
        Http::fake(['*' => Http::response(['result' => ['task' => ['id' => '123']]])]);

        $taskId = app(BitrixReconciliationTaskService::class)->create($this->counterparty());

        $this->assertSame(123, $taskId);
        Http::assertSent(function (Request $request): bool {
            $fields = $request['fields'];

            $this->assertSame('https://example.test/rest/1/secret/tasks.task.add.json', $request->url());
            $this->assertSame('Подготовить акт сверки (Альфа)', $fields['TITLE']);
            $this->assertSame("Подготовить и отправить акт сверки по контрагенту Альфа.\n\nОтправить акт:\n\n1. на эл.почту client@example.test\n2. через ЭДО\n3. приложить pdf к этой задаче", $fields['DESCRIPTION']);
            $this->assertSame(['CO_42'], $fields['UF_CRM_TASK']);
            $this->assertSame(31648, $fields['RESPONSIBLE_ID']);
            $this->assertSame([8, 54, 18, 33036, 6], $fields['AUDITORS']);
            $this->assertSame(16, $fields['FLOW_ID']);
            $this->assertSame(2, $fields['PRIORITY']);
            $this->assertSame([['VALUE' => 'Y', 'CODE' => 3]], $fields['SE_PARAMETER']);
            $this->assertSame('2026-11-23T15:30:00+03:00', $fields['DEADLINE']);

            return true;
        });
        Http::assertSentCount(1);
    }

    public function test_missing_email_is_explicit_in_task(): void
    {
        Http::fake(['*' => Http::response(['result' => 123])]);

        app(BitrixReconciliationTaskService::class)->create($this->counterparty(['email' => null]));

        Http::assertSent(fn (Request $request): bool => str_contains($request['fields']['DESCRIPTION'], 'на эл.почту не указана в карточке контрагента'));
    }

    public function test_missing_crm_binding_blocks_request(): void
    {
        try {
            app(BitrixReconciliationTaskService::class)->create($this->counterparty(['bitrix_company_id' => null]));
            $this->fail('Missing CRM binding must block task creation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('ID компании Б24', $exception->getMessage());
            Http::assertNothingSent();
        }
    }

    public function test_missing_webhook_blocks_request(): void
    {
        config(['services.bitrix24.task_webhook_url' => null]);

        $this->expectException(ValidationException::class);
        app(BitrixReconciliationTaskService::class)->create($this->counterparty());
    }

    public function test_bitrix_error_is_reported_without_webhook_or_response_secrets(): void
    {
        Http::fake(['*' => Http::response(['error' => 'ACCESS_DENIED', 'error_description' => 'secret'])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Б24 не подтвердил создание задачи.');
        app(BitrixReconciliationTaskService::class)->create($this->counterparty());
    }

    public function test_timeout_is_not_retried_and_does_not_expose_webhook(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Проверьте наличие задачи в Б24 перед повторным запросом.');
        app(BitrixReconciliationTaskService::class)->create($this->counterparty());
    }

    private function counterparty(array $attributes = []): Counterparty
    {
        return new Counterparty(array_merge([
            'id' => 10,
            'short_name' => 'Альфа',
            'name' => 'ООО Альфа',
            'email' => 'client@example.test',
            'bitrix_company_id' => 42,
        ], $attributes));
    }
}
