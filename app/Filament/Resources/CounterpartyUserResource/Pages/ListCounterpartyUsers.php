<?php

namespace App\Filament\Resources\CounterpartyUserResource\Pages;

use App\Filament\Resources\CounterpartyUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounterpartyUsers extends ListRecords
{
    protected static string $resource = CounterpartyUserResource::class;

    protected function getHeaderActions(): array
    {
        if (! CounterpartyUserResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
