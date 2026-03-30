<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_templates', function (Blueprint $table): void {
            $table->string('difficulty', 16)->nullable()->after('recipe_type');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_templates', function (Blueprint $table): void {
            $table->dropIndex(['difficulty']);
            $table->dropColumn('difficulty');
        });
    }
};
