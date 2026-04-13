<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_template_interactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('recipe_template_id')->constrained('recipe_templates')->cascadeOnDelete();
            $table->string('interaction_type', 40)->index();
            $table->string('source', 40)->nullable()->index();
            $table->string('goal', 40)->nullable()->index();
            $table->timestampTz('interacted_at')->index();
            $table->timestampsTz();

            $table->unique(
                ['user_id', 'recipe_template_id', 'interaction_type'],
                'recipe_template_interactions_unique'
            );
            $table->index(
                ['user_id', 'interaction_type', 'interacted_at'],
                'recipe_template_interactions_lookup'
            );
        });

        Schema::create('recipe_plan_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('recipe_template_id')->constrained('recipe_templates')->cascadeOnDelete();
            $table->string('horizon', 32)->index();
            $table->string('note', 240)->nullable();
            $table->timestampsTz();

            $table->unique(
                ['user_id', 'recipe_template_id', 'horizon'],
                'recipe_plan_items_unique'
            );
            $table->index(['user_id', 'horizon', 'updated_at'], 'recipe_plan_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_plan_items');
        Schema::dropIfExists('recipe_template_interactions');
    }
};
