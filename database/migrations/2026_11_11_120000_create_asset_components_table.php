<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            // Вид зберігається рядком (не ENUM), щоб міграція однаково працювала на SQLite і MySQL.
            $table->string('kind');
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('holder_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_working')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_components');
    }
};
