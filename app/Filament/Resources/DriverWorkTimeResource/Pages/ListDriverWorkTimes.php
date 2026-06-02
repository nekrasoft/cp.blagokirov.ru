<?php

namespace App\Filament\Resources\DriverWorkTimeResource\Pages;

use App\Filament\Pages\DriverWorkTimeSummaryPage;
use App\Filament\Resources\DriverWorkTimeResource;
use App\Filament\Support\DriverWorkTimeSummary;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDriverWorkTimes extends ListRecords
{
    protected static string $resource = DriverWorkTimeResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('monthlySummary')
                ->label('Итоги по месяцам')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('gray')
                ->url(fn (): string => DriverWorkTimeSummaryPage::getUrl())
                ->visible(fn (): bool => DriverWorkTimeSummary::canBuild()),
        ];

        if (! DriverWorkTimeResource::canCreate()) {
            return $actions;
        }

        return [
            CreateAction::make(),
            ...$actions,
        ];
    }
}
