<?php

namespace Tests\Unit;

use App\Filament\Resources\BunkerPickupReportResource;
use Filament\Schemas\Schema;
use Tests\TestCase;

class BunkerPickupGalleryTest extends TestCase
{
    public function test_report_infolist_schema_can_be_built(): void
    {
        BunkerPickupReportResource::infolist(Schema::make());

        $this->addToAssertionCount(1);
    }

    public function test_site_photos_open_in_a_navigable_modal(): void
    {
        $photos = [
            ['kind' => 'site_photo', 'name' => 'first.jpg', 'size' => 1000, 'url' => '/photos/1', 'photoIndex' => 0],
            ['kind' => 'site_photo', 'name' => 'second.jpg', 'size' => 2000, 'url' => '/photos/2', 'photoIndex' => 1],
        ];
        $html = view('filament.infolists.components.bunker-pickup-files', [
            'reportId' => 42,
            'files' => [...$photos, ['kind' => 'container_waybill', 'name' => 'waybill.pdf', 'size' => 3000, 'url' => '/files/3', 'photoIndex' => null]],
            'photos' => $photos,
        ])->render();

        $this->assertStringContainsString('pickup-photo-gallery-42', $html);
        $this->assertStringContainsString('openPhoto(0)', $html);
        $this->assertStringContainsString('openPhoto(1)', $html);
        $this->assertStringContainsString('class="pickup-files__thumbnail"', $html);
        $this->assertStringContainsString('src="/photos/1"', $html);
        $this->assertStringContainsString('src="/photos/2"', $html);
        $this->assertStringContainsString('class="pickup-files__gallery"', $html);
        $this->assertStringContainsString('>Тип<', $html);
        $this->assertStringContainsString('>Размер<', $html);
        $this->assertStringContainsString('Предыдущее фото', $html);
        $this->assertStringContainsString('Следующее фото', $html);
        $this->assertStringContainsString('href="/files/3"', $html);
        $this->assertLessThan(strpos($html, 'href="/files/3"'), strpos($html, 'class="pickup-files__gallery"'));
        $this->assertStringNotContainsString('target="_blank"', $html);
    }
}
