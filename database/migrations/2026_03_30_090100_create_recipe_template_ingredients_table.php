<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_template_ingredients', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('recipe_template_id')
                ->constrained('recipe_templates')
                ->cascadeOnDelete();
            $table->foreignUlid('ingredient_id')
                ->constrained('ingredients')
                ->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestampsTz();

            $table->unique(['recipe_template_id', 'ingredient_id']);
            $table->index(['recipe_template_id', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_template_ingredients');
    }
};
