<?php

namespace App\Filament\Counterparty\Pages;

use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FeedbackPage extends Page
{
    protected string $view = 'filament.counterparty.pages.feedback-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Оставить отзыв';

    protected static ?string $title = 'Оставить отзыв';

    protected static ?string $slug = 'feedback';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }
}
