<?php

namespace App\Filament\Resources\BunkerFillRequestResource\Pages;

use App\Filament\Resources\BunkerFillRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListBunkerFillRequests extends ListRecords
{
    protected static string $resource = BunkerFillRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
