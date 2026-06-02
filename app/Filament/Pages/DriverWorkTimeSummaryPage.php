<?php

namespace App\Filament\Pages;

use App\Filament\Support\DriverWorkTimeSummary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DriverWorkTimeSummaryPage extends Page
{
    protected string $view = 'filament.pages.driver-work-time-summary-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Итоги времени';

    protected static ?string $title = 'Итоги времени водителей';

    protected static ?string $slug = 'driver-work-time-summary';

    protected static string|UnitEnum|null $navigationGroup = 'Водители';

    protected static ?int $navigationSort = 20;

    public ?string $month = null;

    public function mount(): void
    {
        $this->month = DriverWorkTimeSummary::month(request()->query('month'))->format('Y-m');
    }

    public static function canAccess(): bool
    {
        return DriverWorkTimeSummary::canBuild();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('currentMonth')
                ->label('Текущий месяц')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(fn (): string => static::getUrl([
                    'month' => DriverWorkTimeSummary::month()->format('Y-m'),
                ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'summary' => DriverWorkTimeSummary::forMonth($this->month),
            'monthOptions' => DriverWorkTimeSummary::monthOptions(),
            'selectedMonth' => DriverWorkTimeSummary::month($this->month)->format('Y-m'),
        ];
    }
}
