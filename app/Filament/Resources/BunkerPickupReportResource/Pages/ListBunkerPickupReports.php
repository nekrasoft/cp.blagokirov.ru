<?php

namespace App\Filament\Resources\BunkerPickupReportResource\Pages;

use App\Filament\Resources\BunkerPickupReportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBunkerPickupReports extends ListRecords
{
    protected static string $resource = BunkerPickupReportResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все'),
            'missing_waybill' => Tab::make('Без обязательного талона')
                ->query(fn (Builder $query): Builder => $query->where('waybill_required', true)->whereDoesntHave('waybills')),
            'pending_delivery' => Tab::make('Ожидают записи в таблицу')
                ->query(fn (Builder $query): Builder => $query->whereIn('sheets_status', ['pending', 'sending', 'retry'])),
            'not_cleaned' => Tab::make('Территория не прибрана')
                ->query(fn (Builder $query): Builder => $query->where('cleanup_status', 'not_cleaned')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
