<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('counterparties') && ! Schema::hasColumn('counterparties', 'requires_container_waybill')) {
            Schema::table('counterparties', function (Blueprint $table): void {
                $table->boolean('requires_container_waybill')->default(false);
            });
        }

        if (Schema::hasTable('bunker_fill_requests')) {
            Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                if (! Schema::hasColumn('bunker_fill_requests', 'cancelled_at')) {
                    $table->dateTime('cancelled_at')->nullable();
                }
                if (! Schema::hasColumn('bunker_fill_requests', 'cancellation_reason_code')) {
                    $table->string('cancellation_reason_code', 64)->nullable();
                }
                if (! Schema::hasColumn('bunker_fill_requests', 'cancellation_comment')) {
                    $table->string('cancellation_comment', 500)->nullable();
                }
                if (! Schema::hasColumn('bunker_fill_requests', 'cancelled_by')) {
                    $table->string('cancelled_by')->nullable();
                }
            });

            if (! Schema::hasIndex('bunker_fill_requests', 'idx_bunker_fill_requests_pending')) {
                Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                    $table->index(
                        ['bunker_id', 'executed_at', 'cancelled_at', 'filled_at'],
                        'idx_bunker_fill_requests_pending',
                    );
                });
            }
        }

        if (! Schema::hasTable('bunker_pickup_reports')) {
            Schema::create('bunker_pickup_reports', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('counterparty_id')->nullable();
                $table->string('contractor')->default('');
                $table->string('driver_source', 20);
                $table->string('driver_user_id', 64);
                $table->string('driver_name')->nullable();
                $table->string('cleanup_status', 32);
                $table->string('cleanup_comment', 500)->nullable();
                $table->boolean('waybill_required')->default(false);
                $table->string('waybill_missing_reason', 500)->nullable();
                $table->decimal('billing_units', 8, 2);
                $table->dateTime('completed_at');
                $table->timestamp('created_at')->useCurrent();
                $table->index('completed_at', 'idx_bunker_pickup_reports_completed');
                $table->index(['waybill_required', 'completed_at'], 'idx_bunker_pickup_reports_waybill_completed');
                $table->index(['counterparty_id', 'completed_at'], 'idx_bunker_pickup_reports_counterparty_completed');
                $table->index(['driver_source', 'driver_user_id', 'completed_at'], 'idx_bunker_pickup_reports_driver');
            });
        }

        if (! Schema::hasTable('bunker_pickup_items')) {
            Schema::create('bunker_pickup_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('report_id');
                $table->unsignedBigInteger('request_id')->unique('uq_bunker_pickup_items_request');
                $table->string('bunker_id', 64);
                $table->integer('bunker_number')->default(0);
                $table->decimal('billing_units', 5, 2);
                $table->decimal('estimated_volume_m3', 10, 2);
                $table->timestamp('created_at')->useCurrent();
                $table->index('report_id', 'idx_bunker_pickup_items_report');
            });
        }

        if (! Schema::hasTable('bunker_pickup_files')) {
            Schema::create('bunker_pickup_files', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('report_id');
                $table->string('kind', 32);
                $table->string('file_token', 64)->nullable()->unique('uq_bunker_pickup_files_token');
                $table->string('file_name');
                $table->string('content_type', 100);
                $table->unsignedBigInteger('file_size');
                $table->char('file_sha256', 64);
                $table->binary('file_data');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['report_id', 'kind'], 'idx_bunker_pickup_files_report_kind');
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                Schema::getConnection()->statement(
                    'ALTER TABLE bunker_pickup_files MODIFY file_data MEDIUMBLOB NOT NULL',
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bunker_pickup_files');
        Schema::dropIfExists('bunker_pickup_items');
        Schema::dropIfExists('bunker_pickup_reports');

        if (Schema::hasTable('bunker_fill_requests')) {
            Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                if (Schema::hasIndex('bunker_fill_requests', 'idx_bunker_fill_requests_pending')) {
                    $table->dropIndex('idx_bunker_fill_requests_pending');
                }
                $columns = array_values(array_filter([
                    'cancelled_at',
                    'cancellation_reason_code',
                    'cancellation_comment',
                    'cancelled_by',
                ], fn (string $column): bool => Schema::hasColumn('bunker_fill_requests', $column)));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('counterparties') && Schema::hasColumn('counterparties', 'requires_container_waybill')) {
            Schema::table('counterparties', function (Blueprint $table): void {
                $table->dropColumn('requires_container_waybill');
            });
        }
    }
};
