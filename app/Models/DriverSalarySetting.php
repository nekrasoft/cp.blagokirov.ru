<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverSalarySetting extends Model
{
    protected $fillable = [
        'source',
        'source_user_id',
        'source_user_name',
        'hourly_rate',
        'overtime_threshold_hours',
        'overtime_hourly_rate',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'overtime_threshold_hours' => 'decimal:2',
        'overtime_hourly_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
