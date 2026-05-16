<?php

namespace App\Filament\Resources\BunkerResource\Pages;

use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\Concerns\RedirectsToResourceList;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBunker extends EditRecord
{
    use RedirectsToResourceList;

    protected static string $resource = BunkerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
