<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Support\DashboardMetrics;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BitrixReconciliationTaskService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ListInvoiceDebtors extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Топ-должники';

    public function mount(): void
    {
        parent::mount();

        if (DashboardMetrics::currentCounterpartyUser() || ! DashboardMetrics::canBuildUnpaidInvoiceDebtorsReport()) {
            abort(403);
        }
    }

    public function getBreadcrumb(): ?string
    {
        return 'Топ-должники';
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Топ-должники')
            ->description('Контрагенты с неоплаченными счетами.')
            ->columns([
                TextColumn::make('counterparty_id')
                    ->label('Контрагент')
                    ->state(fn (Invoice $record): string => $this->counterpartyName($record))
                    ->description(fn (Invoice $record): ?string => $record->counterparty_id ? 'ID '.$record->counterparty_id : null)
                    ->weight('medium'),

                TextColumn::make('unpaid_total')
                    ->label('Сумма долга')
                    ->formatStateUsing(fn ($state): string => DashboardMetrics::formatMoney((float) ($state ?? 0)))
                    ->sortable(),

                TextColumn::make('unpaid_invoices_count')
                    ->label('Счетов')
                    ->formatStateUsing(fn ($state): string => DashboardMetrics::formatInteger((int) ($state ?? 0)))
                    ->sortable(),

                TextColumn::make('overdue_invoices_count')
                    ->label('Просрочено')
                    ->badge()
                    ->color(fn ($state): string => ((int) ($state ?? 0)) > 0 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state): string => DashboardMetrics::formatInteger((int) ($state ?? 0)))
                    ->sortable(),

                TextColumn::make('oldest_due_date')
                    ->label('Ближайший срок')
                    ->date('d.m.Y')
                    ->placeholder('без срока')
                    ->sortable(),
            ])
            ->defaultSort('unpaid_total', 'desc')
            ->defaultKeySort(false)
            ->recordUrl(fn (Invoice $record): string => $this->unpaidInvoicesUrl($record))
            ->recordActions([
                Action::make('showInvoices')
                    ->label('Счета')
                    ->icon('heroicon-m-list-bullet')
                    ->url(fn (Invoice $record): string => $this->unpaidInvoicesUrl($record)),
                Action::make('requestReconciliation')
                    ->label('Запросить акт сверки')
                    ->icon('heroicon-m-document-text')
                    ->visible(fn (): bool => $this->canRequestReconciliation())
                    ->disabled(fn (Invoice $record): bool => ! $record->counterparty_id)
                    ->requiresConfirmation()
                    ->modalHeading('Запросить акт сверки')
                    ->modalDescription(fn (Invoice $record): string => 'Создать задачу бухгалтерии по контрагенту «'.$this->counterpartyName($record).'»?')
                    ->action(function (Invoice $record): void {
                        abort_unless($this->canRequestReconciliation(), 403);

                        $counterparty = $record->counterparty;
                        abort_unless($counterparty, 404);

                        try {
                            $taskId = app(BitrixReconciliationTaskService::class)->create($counterparty);
                        } catch (ValidationException $exception) {
                            Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();

                            return;
                        } catch (\RuntimeException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Задача Б24 №'.$taskId.' создана')->success()->send();
                    }),
            ])
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('Должников нет')
            ->emptyStateDescription('Неоплаченных счетов по контрагентам сейчас нет.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unpaidInvoices')
                ->label('Счета к оплате')
                ->icon('heroicon-m-document-text')
                ->url(fn (): string => InvoiceResource::getUrl('index', ['tab' => 'unpaid'])),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return DashboardMetrics::unpaidInvoiceDebtorsQuery()
            ?? Invoice::query()->whereRaw('1 = 0');
    }

    protected function resolveTableRecord(?string $key): ?Invoice
    {
        if ($key === null || ! ctype_digit($key) || (int) $key <= 0) {
            return null;
        }

        // Строки отчёта используют ID контрагента, а не ID отдельного счёта.
        return $this->getTableQuery()->where('invoices.counterparty_id', (int) $key)->first();
    }

    private function counterpartyName(Invoice $record): string
    {
        $counterparty = $record->relationLoaded('counterparty') ? $record->counterparty : null;

        if ($counterparty) {
            $name = trim((string) ($counterparty->short_name ?: $counterparty->name));

            if ($name !== '') {
                return $name;
            }
        }

        return $record->counterparty_id
            ? 'Контрагент #'.$record->counterparty_id
            : 'Без контрагента';
    }

    private function canRequestReconciliation(): bool
    {
        $user = Filament::auth()->user();

        return ! DashboardMetrics::currentCounterpartyUser()
            && $user instanceof User
            && $user->canWriteAdminPanel();
    }

    private function unpaidInvoicesUrl(Invoice $record): string
    {
        $parameters = ['tab' => 'unpaid'];
        $counterpartyId = (int) $record->counterparty_id;

        if ($counterpartyId > 0) {
            $parameters['filters'] = [
                'counterparty_id' => [
                    'value' => (string) $counterpartyId,
                ],
            ];
        }

        return InvoiceResource::getUrl('index', $parameters);
    }
}
