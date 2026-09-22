<?php

namespace App\Filament\Resources\BunkerPickupReportResource\Pages;

use App\Filament\Resources\BunkerPickupReportResource;
use Filament\Resources\Pages\ListRecords;

class ListBunkerPickupReports extends ListRecords
{
    protected static string $resource = BunkerPickupReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
