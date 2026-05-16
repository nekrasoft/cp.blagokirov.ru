<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\Concerns\RedirectsToResourceList;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    use RedirectsToResourceList;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
