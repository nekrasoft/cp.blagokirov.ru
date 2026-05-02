<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CounterpartyUnpaidInvoicesTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 9;

    public static function canView(): bool
    {
        return DashboardMetrics::currentCounterpartyUser() instanceof CounterpartyUser
            && DashboardMetrics::hasTable('invoices')
            && DashboardMetrics::hasColumn('invoices', 'status');
    }

    public function table(Table $table): Table
    {
        $sortColumn = DashboardMetrics::hasColumn('invoices', 'due_date') ? 'due_date' : (DashboardMetrics::hasColumn('invoices', 'issued_at') ? 'issued_at' : 'id');
        $query = DashboardMetrics::unpaidInvoicesQuery()
            ?->orderBy($sortColumn)
            ->limit(8);

        return $table
            ->heading('Счета к оплате')
            ->description('Актуальные счета, которые требуют внимания.')
            ->query(fn (): Builder => $query ?? Invoice::query()->whereRaw('1 = 0'))
            ->columns($this->columns())
            ->paginated(false)
            ->emptyStateHeading('Счетов к оплате нет')
            ->emptyStateDescription('Сейчас у вас нет счетов, требующих оплаты.')
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
                ->copyable()
                ->color(fn (Invoice $record): string => $record->pdf_url ? 'primary' : 'gray')
                ->url(fn (Invoice $record): ?string => $record->pdf_url ?: null, shouldOpenInNewTab: true);
        }

        if (DashboardMetrics::hasColumn('invoices', 'issued_at')) {
            $columns[] = TextColumn::make('issued_at')
                ->label('Выставлен')
                ->date('d.m.Y');
        }

        if (DashboardMetrics::hasColumn('invoices', 'due_date')) {
            $columns[] = TextColumn::make('due_date')
                ->label('Срок')
                ->date('d.m.Y')
                ->color(fn (Invoice $record): string => $this->isOverdue($record) ? 'danger' : 'gray')
                ->icon(fn (Invoice $record): ?string => $this->isOverdue($record) ? 'heroicon-m-exclamation-triangle' : null);
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
