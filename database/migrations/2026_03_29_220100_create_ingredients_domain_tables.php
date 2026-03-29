<?php

use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default(ContentStatus::Published->value)->index();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('ingredient_aliases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('alias', 150);
            $table->string('normalized_alias', 160);
            $table->string('locale', 12)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['ingredient_id', 'normalized_alias', 'locale']);
            $table->index(['normalized_alias', 'locale']);
        });

        Schema::create('pairings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignUlid('paired_ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->unsignedTinyInteger('strength')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 32)->default(ContentStatus::Published->value)->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['ingredient_id', 'paired_ingredient_id']);
        });

        Schema::create('substitutions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignUlid('substitute_ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->string('status', 32)->default(ContentStatus::Published->value)->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['ingredient_id', 'substitute_ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutions');
        Schema::dropIfExists('pairings');
        Schema::dropIfExists('ingredient_aliases');
        Schema::dropIfExists('ingredients');
    }
};
