<?php

namespace App\Http\Controllers;

use App\Filament\Support\BunkerPickupReportScope;
use App\Models\BunkerPickupFile;
use App\Models\BunkerPickupReport;
use App\Models\CounterpartyUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class BunkerPickupFileController extends Controller
{
    public function admin(int $file): Response
    {
        abort_unless(Auth::guard('web')->check(), 403);

        return $this->download($file);
    }

    public function counterparty(int $file): Response
    {
        $user = Auth::guard('counterparty')->user();
        abort_unless($user instanceof CounterpartyUser, 403);

        $pickupFile = BunkerPickupFile::query()->findOrFail($file);
        $allowed = BunkerPickupReportScope::apply(BunkerPickupReport::query(), $user)
            ->whereKey($pickupFile->report_id)
            ->exists();
        abort_unless($allowed, 404);

        return $this->fileResponse($pickupFile);
    }

    private function download(int $file): Response
    {
        return $this->fileResponse(BunkerPickupFile::query()->findOrFail($file));
    }

    private function fileResponse(BunkerPickupFile $file): Response
    {
        $name = str_replace(["\r", "\n", '"'], '', basename((string) $file->file_name));
        $disposition = str_starts_with((string) $file->content_type, 'image/') ? 'inline' : 'attachment';

        return response($file->file_data)
            ->header('Content-Type', (string) $file->content_type)
            ->header('Content-Length', (string) $file->file_size)
            ->header('Content-Disposition', $disposition.'; filename="'.$name.'"')
            ->header('Cache-Control', 'private, no-store');
    }
}
