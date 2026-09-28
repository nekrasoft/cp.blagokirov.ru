<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['bunker_pickup_reports', 'bunker_pickup_items', 'bunkers', 'counterparties'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $reports = DB::table('bunker_pickup_reports as reports')
            ->join('bunker_pickup_items as items', 'items.report_id', '=', 'reports.id')
            ->join('bunkers', 'bunkers.id', '=', 'items.bunker_id')
            ->join('counterparties', 'counterparties.id', '=', 'bunkers.counterparty_id')
            ->whereBetween('reports.completed_at', ['2026-09-28 23:04:00', '2026-09-28 23:04:59'])
            ->where('reports.contractor', 'Азбука')
            ->where('counterparties.short_name', 'СТРОЙМОДА')
            ->select('reports.id', 'bunkers.counterparty_id', 'counterparties.short_name')
            ->distinct()
            ->get();

        foreach ($reports as $report) {
            DB::table('bunker_pickup_reports')->where('id', $report->id)->update([
                'counterparty_id' => $report->counterparty_id,
                'contractor' => $report->short_name,
            ]);
        }
    }

    public function down(): void
    {
        // Возвращать заведомо неверного контрагента нельзя.
    }
};
