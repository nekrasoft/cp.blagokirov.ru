<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use Filament\Facades\Filament;

trait AuthorizesAdminWrites
{
    protected static function hasAdminWriteAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->canWriteAdminPanel();
    }
}
