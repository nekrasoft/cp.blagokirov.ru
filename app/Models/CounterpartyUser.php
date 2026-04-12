<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterpartyUser extends Authenticatable implements FilamentUser
{
    protected $table = 'counterparty_users';

    protected $fillable = [
        'login',
        'password_hash',
        'counterparty_id',
        'district_scope',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'counterparty_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'counterparty'
            && $this->is_active
            && $this->counterparty_id > 0;
    }
}
