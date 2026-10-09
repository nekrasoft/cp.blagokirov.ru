<?php

namespace Tests\Unit;

use App\Filament\Resources\BunkerFillRequestResource\Pages\ListBunkerFillRequests;
use App\Filament\Resources\BunkerPickupReportResource;
use App\Filament\Resources\BunkerPickupReportResource\Pages\ListBunkerPickupReports;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CounterpartyPanelProvider;
use Filament\Facades\Filament;
use Filament\Panel;
use Tests\TestCase;

class PanelNavigationTest extends TestCase
{
    public function test_admin_menu_keeps_billing_before_drivers_and_map_before_bunkers(): void
    {
        config()->set('services.cross_service_sso.map_service_url', 'https://map.example.com');
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());
        Filament::setCurrentPanel($panel);
        $this->assertSame(['Панель', 'Клиенты', 'Вывоз мусора', 'Биллинг', 'Водители'], $panel->getNavigationGroups());
        $this->assertSame('Заявки на вывоз', (new ListBunkerFillRequests)->getTitle());
        $this->assertSame('Отчёты о вывозе', (new ListBunkerPickupReports)->getTitle());
        $map = $panel->getNavigationItems()[0];
        $this->assertSame('Вывоз мусора', $map->getGroup());
        $this->assertSame('https://map.example.com', $map->getUrl());
        $this->assertTrue($map->shouldOpenUrlInNewTab());
        $this->assertLessThan(BunkerResource::getNavigationSort(), $map->getSort());
        $this->assertSame('Биллинг', InvoiceResource::getNavigationGroup());
        $this->assertSame('Отчёты о вывозе', BunkerPickupReportResource::getNavigationLabel());
    }

    public function test_customer_menu_groups_tasks_and_preserves_map_sso(): void
    {
        $panel = (new CounterpartyPanelProvider($this->app))->panel(Panel::make());
        Filament::setCurrentPanel($panel);
        $this->assertSame(['Вывоз мусора', 'Оплата и документы', 'Обратная связь'], $panel->getNavigationGroups());
        $this->assertSame('Оплата и документы', InvoiceResource::getNavigationGroup());
        $this->assertSame('Оплата и документы', WorkResource::getNavigationGroup());
        $this->assertSame('Отчёты о вывозе', BunkerPickupReportResource::getNavigationLabel());
        $this->assertSame('Заявки на вывоз', (new ListBunkerFillRequests)->getTitle());
        $this->assertSame('Отчёты о вывозе', (new ListBunkerPickupReports)->getTitle());
        $map = $panel->getNavigationItems()[0];
        $this->assertSame('Вывоз мусора', $map->getGroup());
        $this->assertSame(route('billing.sso.map'), $map->getUrl());
        $this->assertTrue($map->shouldOpenUrlInNewTab());
    }
}
