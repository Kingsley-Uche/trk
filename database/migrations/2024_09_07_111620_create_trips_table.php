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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id')->unique(); // Unique trip ID
            $table->string('name'); // Trip name
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade'); // Reference to the driver
            $table->string('vehicle_vin'); // Reference to the vehicle by VIN
            $table->foreign('vehicle_vin')->references('vin')->on('vehicles')->onDelete('cascade'); // Set up foreign key constraint for VIN
            $table->text('description')->nullable(); // Description of the trip (optional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
