<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BunkerPickupItem extends Model
{
    protected $table = 'bunker_pickup_items';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'report_id' => 'integer',
        'request_id' => 'integer',
        'bunker_number' => 'integer',
        'billing_units' => 'decimal:2',
        'estimated_volume_m3' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(BunkerPickupReport::class, 'report_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(BunkerFillRequest::class, 'request_id');
    }
}
