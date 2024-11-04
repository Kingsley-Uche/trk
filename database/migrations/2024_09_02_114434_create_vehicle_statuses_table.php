<?php

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
        Schema::create('vehicle_statuses', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade'); // Correct foreign key reference
            $table->string('vehicle_vin'); // Store VIN as a string, not as a foreignId
            $table->enum('vehicle_status', ['idling', 'moving', 'offline', 'parked']); // Correctly using enum with an array
            $table->string('device_id'); // Device ID as a string
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_statuses'); // Drop the vehicle_statuses table if it exists
    }
};