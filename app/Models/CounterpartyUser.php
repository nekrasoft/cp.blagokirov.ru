<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterpartyUser extends Model
{
    protected $table = 'counterparty_users';

    protected $fillable = [
        'login',
        'password_hash',
        'counterparty_id',
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
}

