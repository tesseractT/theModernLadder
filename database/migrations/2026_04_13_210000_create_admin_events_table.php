<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('stream', 64)->index();
            $table->string('event', 120)->index();
            $table->ulid('actor_user_id')->nullable()->index();
            $table->string('target_type', 120)->nullable()->index();
            $table->string('target_id', 120)->nullable()->index();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('route_name', 160)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->timestampTz('created_at')->nullable();

            $table->index(['stream', 'occurred_at']);
            $table->index(['target_type', 'target_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_events');
    }
};
