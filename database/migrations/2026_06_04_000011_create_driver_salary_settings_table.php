<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'driver_salary_settings';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('source', 50);
            $table->string('source_user_id', 64);
            $table->string('source_user_name')->nullable();
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('overtime_threshold_hours', 8, 2);
            $table->decimal('overtime_hourly_rate', 10, 2);
            $table->timestamps();

            $table->unique(['source', 'source_user_id'], 'uniq_driver_salary_settings_source_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
