<?php

namespace App\Filament\Resources\CounterpartyUserResource\Pages;

use App\Filament\Resources\CounterpartyUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCounterpartyUser extends EditRecord
{
    protected static string $resource = CounterpartyUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

