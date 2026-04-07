<?php

namespace App\Filament\Resources\CounterpartyResource\Pages;

use App\Filament\Resources\CounterpartyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCounterparty extends EditRecord
{
    protected static string $resource = CounterpartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

