<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use App\Models\Work;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CounterpartyRecentWorksTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 8;

    public static function canView(): bool
    {
        return DashboardMetrics::currentCounterpartyUser() instanceof CounterpartyUser
            && DashboardMetrics::hasTable('works');
    }

    public function table(Table $table): Table
    {
        $sortColumn = DashboardMetrics::hasColumn('works', 'date') ? 'date' : 'id';
        $query = DashboardMetrics::worksQuery()
            ?->when(DashboardMetrics::hasColumn('works', 'invoice_id'), fn (Builder $query): Builder => $query->with('invoice'))
            ->orderByDesc($sortColumn)
            ->limit(8);

        return $table
            ->heading('Последние работы')
            ->description('Последние загруженные операции по вашему договору.')
            ->query(fn (): Builder => $query ?? Work::query()->whereRaw('1 = 0'))
            ->columns($this->columns())
            ->paginated(false)
            ->emptyStateHeading('Работ пока нет')
            ->emptyStateDescription('Когда появятся выполненные работы, они будут показаны здесь.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        $columns = [];

        if (DashboardMetrics::hasColumn('works', 'date')) {
            $columns[] = TextColumn::make('date')
                ->label('Дата')
                ->date('d.m.Y')
                ->sortable();
        }

        if (DashboardMetrics::hasColumn('works', 'structure')) {
            $columns[] = TextColumn::make('structure')
                ->label('Тип')
                ->searchable();
        }

        if (DashboardMetrics::hasColumn('works', 'note')) {
            $columns[] = TextColumn::make('note')
                ->label('Описание')
                ->wrap()
                ->searchable();
        }

        if (DashboardMetrics::hasColumn('works', 'object_count')) {
            $columns[] = TextColumn::make('object_count')
                ->label('Кол-во');
        }

        if (DashboardMetrics::hasColumn('works', 'revenue')) {
            $columns[] = TextColumn::make('revenue')
                ->label('Сумма')
                ->formatStateUsing(fn ($state): string => number_format((float) ($state ?? 0), 2, ',', ' ') . ' ₽')
                ->sortable();
        }

        if (DashboardMetrics::hasColumn('works', 'invoice_id') && DashboardMetrics::hasTable('invoices')) {
            $columns[] = TextColumn::make('invoice.status')
                ->label('Оплата')
                ->badge()
                ->formatStateUsing(function (?string $state, Work $record): string {
                    if (! $record->invoice_id) {
                        return 'счет не выставлен';
                    }

                    return match ($state) {
                        'paid' => 'оплачен',
                        'issued' => 'не оплачен',
                        'pending' => 'в обработке',
                        'failed' => 'ошибка',
                        'cancelled' => 'отменен',
                        'draft' => 'черновик',
                        default => 'неизвестно',
                    };
                })
                ->color(function (?string $state, Work $record): string {
                    if (! $record->invoice_id) {
                        return 'gray';
                    }

                    return match ($state) {
                        'paid' => 'success',
                        'issued' => 'warning',
                        'pending' => 'info',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    };
                });
        }

        return $columns;
    }
}
