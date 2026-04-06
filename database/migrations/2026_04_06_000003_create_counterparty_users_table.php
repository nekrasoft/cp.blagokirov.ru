<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('counterparty_users')) {
            return;
        }

        Schema::create('counterparty_users', function (Blueprint $table): void {
            $table->id();
            $table->string('login', 191)->unique();
            $table->string('password_hash');
            $table->unsignedInteger('counterparty_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('counterparty_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparty_users');
    }
};
