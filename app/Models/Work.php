<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Work extends Model
{
    protected $table = 'works';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'counterparty_name',
        'note',
        'structure',
        'operation',
        'object_count',
        'revenue',
        'sheet_row_hash',
        'invoice_id',
        'created_at',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'decimal:2',
        'invoice_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

