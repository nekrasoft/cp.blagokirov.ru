<?php

namespace App\Filament\Resources\DriverSalarySettingResource\Pages;

use App\Filament\Resources\DriverSalarySettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDriverSalarySetting extends EditRecord
{
    protected static string $resource = DriverSalarySettingResource::class;

    protected function getHeaderActions(): array
    {
        if (! DriverSalarySettingResource::canDelete($this->record)) {
            return [];
        }

        return [
            DeleteAction::make(),
        ];
    }
}
