<?php

use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('summary')->nullable();
            $table->longText('instructions')->nullable();
            $table->unsignedSmallInteger('servings')->nullable();
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->unsignedSmallInteger('cook_minutes')->nullable();
            $table->string('status', 32)->default(ContentStatus::Published->value)->index();
            $table->foreignUlid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_templates');
    }
};
