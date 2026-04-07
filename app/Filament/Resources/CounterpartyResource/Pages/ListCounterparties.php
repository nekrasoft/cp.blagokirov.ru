<?php

namespace App\Filament\Resources\CounterpartyResource\Pages;

use App\Filament\Resources\CounterpartyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounterparties extends ListRecords
{
    protected static string $resource = CounterpartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

