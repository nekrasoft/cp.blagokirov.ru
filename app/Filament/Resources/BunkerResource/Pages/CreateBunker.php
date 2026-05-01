<?php

namespace App\Filament\Resources\BunkerResource\Pages;

use App\Filament\Resources\BunkerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBunker extends CreateRecord
{
    protected static string $resource = BunkerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id'] = filled($data['id'] ?? null) ? (string) $data['id'] : (string) Str::uuid();

        return $data;
    }
}
