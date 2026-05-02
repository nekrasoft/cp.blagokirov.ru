<?php

namespace App\Filament\Resources\BunkerResource\Pages;

use App\Filament\Resources\BunkerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBunkers extends ListRecords
{
    protected static string $resource = BunkerResource::class;

    protected function getHeaderActions(): array
    {
        if (! BunkerResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
