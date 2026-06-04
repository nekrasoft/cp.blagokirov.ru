<?php

namespace App\Filament\Resources\DriverSalarySettingResource\Pages;

use App\Filament\Resources\DriverSalarySettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDriverSalarySettings extends ListRecords
{
    protected static string $resource = DriverSalarySettingResource::class;

    protected function getHeaderActions(): array
    {
        if (! DriverSalarySettingResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
