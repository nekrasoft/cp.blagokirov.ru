<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bunker extends Model
{
    protected $table = 'bunkers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'number',
        'volume',
        'address',
        'district',
        'contractor',
        'counterparty_id',
        'waste_type',
        'last_pickup_date',
        'fill_level',
        'last_filled_at',
        'last_filled_by',
        'contact_phone',
        'lat',
        'lng',
    ];

    protected $casts = [
        'number' => 'integer',
        'volume' => 'decimal:2',
        'counterparty_id' => 'integer',
        'fill_level' => 'integer',
        'last_filled_at' => 'datetime',
        'lat' => 'decimal:14',
        'lng' => 'decimal:14',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }
}
