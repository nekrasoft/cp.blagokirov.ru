<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BunkerPickupReport extends Model
{
    protected $table = 'bunker_pickup_reports';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'counterparty_id' => 'integer',
        'waybill_required' => 'boolean',
        'billing_units' => 'decimal:2',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BunkerPickupItem::class, 'report_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(BunkerPickupFile::class, 'report_id')->select([
            'id',
            'report_id',
            'kind',
            'file_token',
            'file_name',
            'content_type',
            'file_size',
            'file_sha256',
            'created_at',
        ]);
    }

    public function sitePhotos(): HasMany
    {
        return $this->files()->where('kind', 'site_photo');
    }

    public function waybills(): HasMany
    {
        return $this->files()->where('kind', 'container_waybill');
    }
}
