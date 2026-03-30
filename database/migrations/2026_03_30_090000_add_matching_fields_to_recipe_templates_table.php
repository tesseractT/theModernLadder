<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_templates', function (Blueprint $table): void {
            $table->string('recipe_type', 32)->nullable()->after('slug');
            $table->json('dietary_patterns')->nullable()->after('recipe_type');
            $table->index('recipe_type');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_templates', function (Blueprint $table): void {
            $table->dropIndex(['recipe_type']);
            $table->dropColumn(['recipe_type', 'dietary_patterns']);
        });
    }
};
