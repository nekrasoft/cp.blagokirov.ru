<?php

namespace App\Services;

use App\Models\BunkerPickupFile;
use App\Models\BunkerPickupReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BunkerPickupWaybillService
{
    public function attach(BunkerPickupReport $report, array $uploads): void
    {
        DB::transaction(function () use ($report, $uploads): void {
            $report = BunkerPickupReport::query()->lockForUpdate()->findOrFail($report->getKey());
            if ($uploads === [] || $report->waybills()->count() + count($uploads) > 5) {
                throw ValidationException::withMessages(['waybills' => 'В отчёте может быть до 5 талонов.']);
            }
            foreach ($uploads as $upload) {
                if (! $upload instanceof UploadedFile || ! $upload->isValid()
                    || $upload->getSize() > 10 * 1024 * 1024
                    || ! in_array($upload->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
                    throw ValidationException::withMessages(['waybills' => 'Приложите JPEG, PNG, WebP или PDF до 10 МБ.']);
                }
                $bytes = file_get_contents($upload->getRealPath());
                BunkerPickupFile::query()->create([
                    'report_id' => $report->getKey(),
                    'kind' => 'container_waybill',
                    'file_name' => mb_substr(basename($upload->getClientOriginalName()), 0, 255),
                    'content_type' => $upload->getMimeType(),
                    'file_size' => strlen($bytes),
                    'file_sha256' => hash('sha256', $bytes),
                    'file_data' => $bytes,
                    'created_at' => now(),
                ]);
            }
            $report->update(['waybill_missing_reason' => null]);
        });
    }
}
