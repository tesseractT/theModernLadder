<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 100);
            $table->text('bio')->nullable();
            $table->string('locale', 12)->default('en');
            $table->string('timezone', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->timestampsTz();
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->json('value');
            $table->timestampsTz();

            $table->unique(['user_id', 'key']);
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('profiles');
    }
};
