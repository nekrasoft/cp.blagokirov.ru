<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserAdminAccessTest extends TestCase
{
    public function test_admin_can_read_and_write_admin_panel(): void
    {
        $user = new User([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->assertTrue($user->canReadAdminPanel());
        $this->assertTrue($user->canWriteAdminPanel());
    }

    public function test_readonly_admin_can_read_but_cannot_write_admin_panel(): void
    {
        $user = new User([
            'role' => User::ROLE_READONLY_ADMIN,
            'is_active' => true,
        ]);

        $this->assertTrue($user->canReadAdminPanel());
        $this->assertFalse($user->canWriteAdminPanel());
    }

    public function test_inactive_admin_cannot_read_or_write_admin_panel(): void
    {
        $user = new User([
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
        ]);

        $this->assertFalse($user->canReadAdminPanel());
        $this->assertFalse($user->canWriteAdminPanel());
    }
}
