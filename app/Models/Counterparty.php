<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counterparty extends Model
{
    protected $table = 'counterparties';

    public $timestamps = false;

    protected $guarded = [];

    public function counterpartyUsers(): HasMany
    {
        return $this->hasMany(CounterpartyUser::class, 'counterparty_id');
    }
}

