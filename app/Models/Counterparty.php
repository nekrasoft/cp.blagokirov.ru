<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counterparty extends Model
{
    protected $table = 'counterparties';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'bitrix_company_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function counterpartyUsers(): HasMany
    {
        return $this->hasMany(CounterpartyUser::class, 'counterparty_id');
    }
}
