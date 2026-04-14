<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';

    public $timestamps = false;

    protected $fillable = [
        'invoice_number',
        'tbank_invoice_id',
        'counterparty_id',
        'issued_at',
        'due_date',
        'status',
        'pdf_url',
        'paid_amount',
        'paid_at',
        'bitrix_task_id',
        'bitrix_deal_id',
        'created_at',
    ];

    protected $casts = [
        'counterparty_id' => 'integer',
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'bitrix_task_id' => 'integer',
        'bitrix_deal_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'invoice_id');
    }
}
