<?php

use App\Enums\AssetStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('inventory_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->year('year')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('asset_types')->nullOnDelete();
            $table->foreignId('custodian_id')->constrained('employees');
            $table->foreignId('holder_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', array_column(AssetStatus::cases(), 'value'))->default(AssetStatus::BALANCE);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
