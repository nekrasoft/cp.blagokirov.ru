<?php

namespace App\Filament\Resources\BunkerPickupReportResource\Pages;

use App\Filament\Resources\BunkerPickupReportResource;
use App\Models\User;
use App\Services\BunkerPickupWaybillService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBunkerPickupReport extends ViewRecord
{
    protected static string $resource = BunkerPickupReportResource::class;

    public function getTitle(): string
    {
        return 'Отчёт о вывозе';
    }

    public function getBreadcrumb(): string
    {
        return 'Просмотр';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('attachWaybills')
                ->label('Добавить талоны')
                ->visible(fn (): bool => Filament::auth()->user() instanceof User
                    && Filament::auth()->user()->canWriteAdminPanel())
                ->schema([
                    FileUpload::make('waybills')
                        ->label('Подписанные талоны')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(10240)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless(Filament::auth()->user() instanceof User
                        && Filament::auth()->user()->canWriteAdminPanel(), 403);
                    app(BunkerPickupWaybillService::class)->attach($this->getRecord(), $data['waybills']);
                    $this->getRecord()->refresh();
                    Notification::make()->title('Талоны добавлены к отчёту')->success()->send();
                }),
        ];
    }
}
