<?php

namespace Tests\Unit;

use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use Tests\TestCase;

class PreservesNavigationSearchTest extends TestCase
{
    protected function tearDown(): void
    {
        request()->query->replace([]);
        request()->request->replace([]);
        request()->headers->remove('Referer');

        parent::tearDown();
    }

    public function test_empty_query_search_does_not_fall_back_to_referer(): void
    {
        request()->query->set('search', '');
        request()->headers->set('Referer', 'http://localhost/admin/counterparties?search=old');

        $this->assertSame([], PreservesNavigationSearchTestResource::currentSearch());
    }

    public function test_empty_livewire_table_search_does_not_fall_back_to_referer(): void
    {
        request()->request->set('components', [
            [
                'updates' => [
                    'tableSearch' => '',
                ],
            ],
        ]);
        request()->headers->set('Referer', 'http://localhost/admin/counterparties?search=old');

        $this->assertSame([], PreservesNavigationSearchTestResource::currentSearch());
    }

    public function test_livewire_table_search_overrides_referer(): void
    {
        request()->request->set('components', [
            [
                'updates' => [
                    'tableSearch' => 'current',
                ],
            ],
        ]);
        request()->headers->set('Referer', 'http://localhost/admin/counterparties?search=old');

        $this->assertSame(['search' => 'current'], PreservesNavigationSearchTestResource::currentSearch());
    }
}

class PreservesNavigationSearchTestResource
{
    use PreservesNavigationSearch;

    public static function currentSearch(): array
    {
        return static::currentSearchQueryParameter();
    }
}
