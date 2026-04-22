<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BunkerFillRequest extends Model
{
    protected $table = 'bunker_fill_requests';

    public $timestamps = false;

    protected $fillable = [
        'bunker_id',
        'bunker_number',
        'counterparty_id',
        'contractor',
        'district',
        'address',
        'waste_type',
        'fill_level',
        'filled_by',
        'filled_at',
        'created_at',
    ];

    protected $casts = [
        'bunker_number' => 'integer',
        'counterparty_id' => 'integer',
        'fill_level' => 'integer',
        'filled_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }
}
