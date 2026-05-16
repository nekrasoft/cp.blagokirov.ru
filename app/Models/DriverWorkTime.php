<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWorkTime extends Model
{
    protected $table = 'driver_work_time';

    protected $fillable = [
        'source',
        'source_chat_id',
        'source_user_id',
        'source_user_name',
        'work_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'raw_start_text',
        'raw_end_text',
    ];

    protected $casts = [
        'work_date' => 'date',
        'duration_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function prepareAdminFormData(array $data): array
    {
        $durationMinutes = static::calculateDurationMinutes(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
        );

        if ($durationMinutes === null) {
            return $data;
        }

        $data['start_time'] = static::normalizeTime($data['start_time'] ?? null);
        $data['end_time'] = static::normalizeTime($data['end_time'] ?? null);
        $data['duration_minutes'] = $durationMinutes;

        return $data;
    }

    public static function calculateDurationMinutes(?string $startTime, ?string $endTime): ?int
    {
        $startMinutes = static::timeToMinutes($startTime);
        $endMinutes = static::timeToMinutes($endTime);

        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            return null;
        }

        return $endMinutes - $startMinutes;
    }

    public static function normalizeTime(?string $value): ?string
    {
        $minutes = static::timeToMinutes($value);

        if ($minutes === null) {
            return null;
        }

        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    protected static function timeToMinutes(?string $value): ?int
    {
        $value = trim((string) $value);

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}
