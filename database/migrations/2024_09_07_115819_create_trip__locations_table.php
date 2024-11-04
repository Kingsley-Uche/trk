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
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')
                  ->constrained('trips')
                  ->onDelete('cascade');
             $table->string('start_location'); // Start location of the trip
            $table->decimal('start_lon', 10, 7); // Longitude of start location
            $table->decimal('start_lat', 10, 7); // Latitude of start location
            $table->string('end_location'); // End location of the trip
            $table->decimal('end_lat', 10, 7); // Latitude of end location
            $table->decimal('end_lon', 10, 7); // Longitude of end location
             $table->dateTime('departure_time'); // Departure time of the trip
            $table->dateTime('arrival_time')->nullable(); // Arrival time of the trip (optional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip__locations');
    }
};
