<?php

namespace App\Services;

use App\Models\Counterparty;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BitrixReconciliationTaskService
{
    public function create(Counterparty $counterparty): int
    {
        $webhook = trim((string) config('services.bitrix24.task_webhook_url'));

        if ($webhook === '') {
            throw ValidationException::withMessages(['reconciliation' => 'Не настроен webhook Б24 для создания задач.']);
        }

        if ((int) $counterparty->bitrix_company_id <= 0) {
            throw ValidationException::withMessages(['reconciliation' => 'В карточке контрагента не указан ID компании Б24.']);
        }

        $name = trim((string) ($counterparty->short_name ?: $counterparty->name));
        $email = trim((string) $counterparty->email) ?: 'не указана в карточке контрагента';
        $deadline = now('Europe/Moscow')->addWeekday();

        try {
            $response = Http::connectTimeout(5)->timeout(20)->post(rtrim($webhook, '/').'/tasks.task.add.json', [
                'fields' => [
                    'TITLE' => mb_substr('Подготовить акт сверки ('.$name.')', 0, 255),
                    'DESCRIPTION' => "Подготовить и отправить акт сверки по контрагенту {$name}.\n\nОтправить акт:\n\n1. на эл.почту {$email}\n2. через ЭДО\n3. приложить pdf к этой задаче",
                    'DESCRIPTION_IN_BBCODE' => 'N',
                    'RESPONSIBLE_ID' => 31648,
                    'AUDITORS' => [8, 54, 18, 33036, 6],
                    'FLOW_ID' => 16,
                    'PRIORITY' => 2,
                    'DEADLINE' => $deadline->toIso8601String(),
                    'SE_PARAMETER' => [['VALUE' => 'Y', 'CODE' => 3]],
                    'TAGS' => ['киров', 'акт_сверки', 'отправить в ЭДО'],
                    'UF_CRM_TASK' => ['CO_'.(int) $counterparty->bitrix_company_id],
                ],
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Не удалось получить ответ Б24. Проверьте наличие задачи в Б24 перед повторным запросом.');
        }

        $result = $response->json('result');
        $taskId = is_array($result)
            ? ($result['task']['id'] ?? $result['task']['ID'] ?? $result['id'] ?? $result['ID'] ?? null)
            : $result;

        if (! $response->successful() || $response->json('error') || ! ctype_digit((string) $taskId) || (int) $taskId <= 0) {
            throw new RuntimeException('Б24 не подтвердил создание задачи. Проверьте наличие задачи в Б24 перед повторным запросом.');
        }

        return (int) $taskId;
    }
}
