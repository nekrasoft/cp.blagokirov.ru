<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UnpaidInvoicesTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return ! DashboardMetrics::currentCounterpartyUser()
            && DashboardMetrics::hasTable('invoices')
            && DashboardMetrics::hasColumn('invoices', 'status');
    }

    public function table(Table $table): Table
    {
        $sortColumn = DashboardMetrics::hasColumn('invoices', 'due_date') ? 'due_date' : (DashboardMetrics::hasColumn('invoices', 'issued_at') ? 'issued_at' : 'id');
        $query = DashboardMetrics::unpaidInvoicesQuery()
            ?->when(DashboardMetrics::hasColumn('invoices', 'counterparty_id'), fn (Builder $query): Builder => $query->with('counterparty'))
            ->orderBy($sortColumn)
            ->limit(8);

        return $table
            ->heading('Неоплаченные счета')
            ->description('Счета в статусах issued/pending или без статуса.')
            ->query(fn (): Builder => $query ?? Invoice::query()->whereRaw('1 = 0'))
            ->columns($this->columns())
            ->paginated(false)
            ->emptyStateHeading('Неоплаченных счетов нет')
            ->emptyStateDescription('Все выставленные счета закрыты или не требуют внимания.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        $columns = [];

        if (DashboardMetrics::hasColumn('invoices', 'invoice_number')) {
            $columns[] = TextColumn::make('invoice_number')
                ->label('Счет')
                ->searchable()
                ->color(fn (Invoice $record): string => $record->pdf_url ? 'primary' : 'gray')
                ->url(fn (Invoice $record): ?string => $record->pdf_url ?: null, shouldOpenInNewTab: true);
        }

        if (DashboardMetrics::hasTable('counterparties') && DashboardMetrics::hasColumn('invoices', 'counterparty_id')) {
            $columns[] = TextColumn::make(DashboardMetrics::hasColumn('counterparties', 'short_name') ? 'counterparty.short_name' : 'counterparty.name')
                ->label('Контрагент')
                ->searchable();
        }

        if (DashboardMetrics::hasColumn('invoices', 'issued_at')) {
            $columns[] = TextColumn::make('issued_at')
                ->label('Выставлен')
                ->date('d.m.Y')
                ->sortable();
        }

        if (DashboardMetrics::hasColumn('invoices', 'due_date')) {
            $columns[] = TextColumn::make('due_date')
                ->label('Срок')
                ->date('d.m.Y')
                ->color(fn (Invoice $record): string => $this->isOverdue($record) ? 'danger' : 'gray')
                ->icon(fn (Invoice $record): ?string => $this->isOverdue($record) ? 'heroicon-m-exclamation-triangle' : null)
                ->sortable();
        }

        $columns[] = TextColumn::make('status')
            ->label('Статус')
            ->badge()
            ->formatStateUsing(fn (?string $state): string => match ($state) {
                'issued' => 'не оплачен',
                'pending' => 'в обработке',
                default => 'без статуса',
            })
            ->color(fn (?string $state): string => match ($state) {
                'issued' => 'warning',
                'pending' => 'info',
                default => 'gray',
            });

        return $columns;
    }

    private function isOverdue(Invoice $record): bool
    {
        return $record->due_date
            && $record->due_date->isPast()
            && ! $record->due_date->isToday();
    }
}
