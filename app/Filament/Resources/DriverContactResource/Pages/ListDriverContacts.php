<?php

namespace App\Filament\Resources\DriverContactResource\Pages;

use App\Filament\Resources\DriverContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDriverContacts extends ListRecords
{
    protected static string $resource = DriverContactResource::class;

    protected function getHeaderActions(): array
    {
        return DriverContactResource::canCreate() ? [CreateAction::make()] : [];
    }
}
