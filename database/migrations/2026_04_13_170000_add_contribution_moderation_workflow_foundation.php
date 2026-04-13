<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->string('type', 80)->nullable()->after('action');
            $table->index('type');
        });

        DB::table('contributions')
            ->where('status', 'submitted')
            ->update(['status' => 'pending']);

        DB::table('contributions')
            ->where('status', 'accepted')
            ->update(['status' => 'approved']);

        DB::table('contributions')
            ->where('status', 'withdrawn')
            ->update(['status' => 'rejected']);

        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contribution_id')->constrained('contributions')->cascadeOnDelete();
            $table->foreignUlid('moderation_case_id')->nullable()->constrained('moderation_cases')->nullOnDelete();
            $table->foreignUlid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32)->index();
            $table->string('from_status', 32)->nullable()->index();
            $table->string('to_status', 32)->nullable()->index();
            $table->string('reason_code', 80)->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('request_id', 128)->nullable();
            $table->timestampsTz();

            $table->index(['contribution_id', 'action']);
            $table->index(['actor_user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');

        DB::table('contributions')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);

        DB::table('contributions')
            ->where('status', 'approved')
            ->update(['status' => 'accepted']);

        DB::table('contributions')
            ->where('status', 'flagged')
            ->update(['status' => 'submitted']);

        Schema::table('contributions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
