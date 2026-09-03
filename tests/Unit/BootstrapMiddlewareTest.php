<?php

namespace Tests\Unit;

use Tests\TestCase;

class BootstrapMiddlewareTest extends TestCase
{
    public function test_web_middleware_group_is_registered(): void
    {
        $this->assertArrayHasKey('web', app('router')->getMiddlewareGroups());
    }
}
