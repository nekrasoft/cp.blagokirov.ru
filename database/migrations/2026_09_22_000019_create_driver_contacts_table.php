<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_contacts')) {
            return;
        }

        Schema::create('driver_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('source', 20)->nullable();
            $table->string('source_user_id', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['source', 'source_user_id'], 'uq_driver_contacts_source_user');
            $table->index(['is_active', 'name'], 'idx_driver_contacts_active_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_contacts');
    }
};
