<?php

namespace App\Filament\Resources\WorkResource\Pages;

use App\Filament\Resources\WorkResource;
use App\Filament\Widgets\UnpaidInvoicesWarning;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorks extends ListRecords
{
    protected static string $resource = WorkResource::class;

    protected function getHeaderActions(): array
    {
        if (! WorkResource::canCreate()) {
            return [];
        }

        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UnpaidInvoicesWarning::class,
        ];
    }
}
