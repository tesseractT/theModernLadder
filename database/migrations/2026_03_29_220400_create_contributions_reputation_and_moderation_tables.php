<?php

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Moderation\Domain\Enums\ModerationCaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableUlidMorphs('subject');
            $table->string('action', 32)->index();
            $table->string('status', 32)->default(ContributionStatus::Submitted->value)->index();
            $table->json('payload')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->index(['submitted_by_user_id', 'status']);
        });

        Schema::create('contributor_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->unsignedInteger('accepted_contributions_count')->default(0);
            $table->unsignedInteger('rejected_contributions_count')->default(0);
            $table->timestampTz('last_contribution_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('moderation_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->nullableUlidMorphs('subject');
            $table->foreignUlid('contribution_id')->nullable()->constrained('contributions')->nullOnDelete();
            $table->foreignUlid('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default(ModerationCaseStatus::Open->value)->index();
            $table->string('reason_code', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index(['reported_by_user_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_cases');
        Schema::dropIfExists('contributor_scores');
        Schema::dropIfExists('contributions');
    }
};
