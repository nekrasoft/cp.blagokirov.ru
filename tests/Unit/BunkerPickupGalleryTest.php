<?php

namespace Tests\Unit;

use Tests\TestCase;

class BunkerPickupGalleryTest extends TestCase
{
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
        $this->assertStringContainsString('Предыдущее фото', $html);
        $this->assertStringContainsString('Следующее фото', $html);
        $this->assertStringContainsString('href="/files/3"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }
}
