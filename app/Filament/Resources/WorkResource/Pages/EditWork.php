<?php

namespace App\Filament\Resources\WorkResource\Pages;

use App\Filament\Resources\Concerns\RedirectsToResourceList;
use App\Filament\Resources\WorkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWork extends EditRecord
{
    use RedirectsToResourceList;

    protected static string $resource = WorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
