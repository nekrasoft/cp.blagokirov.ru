<?php

namespace App\Filament\Resources\DriverWorkTimeResource\Pages;

use App\Filament\Resources\DriverWorkTimeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDriverWorkTimes extends ListRecords
{
    protected static string $resource = DriverWorkTimeResource::class;

    protected function getHeaderActions(): array
    {
        if (! DriverWorkTimeResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
