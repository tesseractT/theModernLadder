<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_template_steps', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('recipe_template_id')
                ->constrained('recipe_templates')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('instruction');
            $table->timestampsTz();

            $table->unique(['recipe_template_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_template_steps');
    }
};
