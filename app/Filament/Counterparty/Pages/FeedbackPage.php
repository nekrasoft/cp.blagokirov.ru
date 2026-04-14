<?php

namespace App\Filament\Counterparty\Pages;

use App\Models\CounterpartyUser;
use App\Services\GoogleBusinessProfileReviewsService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FeedbackPage extends Page
{
    protected string $view = 'filament.counterparty.pages.feedback-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Отзывы';

    protected static ?string $title = 'Отзывы';

    protected static ?string $slug = 'feedback';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $googleReviews = app(GoogleBusinessProfileReviewsService::class)->getReviews();

        return [
            'googleReviewsEnabled' => (bool) ($googleReviews['enabled'] ?? false),
            'googleReviews' => is_array($googleReviews['reviews'] ?? null) ? $googleReviews['reviews'] : [],
            'googleReviewsError' => isset($googleReviews['error']) ? (string) $googleReviews['error'] : null,
        ];
    }
}
