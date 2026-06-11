<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'name',
        'price',
        'amount',
        'unit',
        'vat',
    ];

    protected $casts = [
        'invoice_id' => 'integer',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
