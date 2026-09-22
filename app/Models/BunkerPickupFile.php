<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BunkerPickupFile extends Model
{
    protected $table = 'bunker_pickup_files';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['file_data'];

    protected $casts = [
        'report_id' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(BunkerPickupReport::class, 'report_id');
    }
}
