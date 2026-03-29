<?php

use App\Modules\Pantry\Domain\Enums\PantryItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantry_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('entered_name', 160);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->text('note')->nullable();
            $table->string('status', 32)->default(PantryItemStatus::Active->value)->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantry_items');
    }
};
