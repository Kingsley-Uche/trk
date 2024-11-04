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
        Schema::create('historical_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('tracker_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2);
            $table->string('speed_unit')->nullable(); // Added to store speed units (km/h, mph)
            $table->integer('course');
            $table->timestamp('fix_time');
            $table->integer('satellite_count'); // Added to store satellite count
            $table->integer('active_satellite_count'); // Added to store active satellite count
            $table->boolean('real_time_gps');
            $table->boolean('gps_positioned');
            $table->boolean('east_longitude');
            $table->boolean('north_latitude');
            $table->integer('mcc');
            $table->integer('mnc');
            $table->integer('lac');
            $table->integer('cell_id');
            $table->string('serial_number'); // Changed to string for serial number
            $table->integer('error_check');
            $table->json('event'); // Stores event data
            $table->bigInteger('parse_time'); // Changed to bigInteger to accommodate large values
            $table->json('terminal_info')->nullable();
            $table->string('voltage_level')->nullable(); // Added to store voltage level
            $table->string('gsm_signal_strength')->nullable(); // Added to store GSM signal strength
            $table->json('response_msg')->nullable(); // Added to store response messages
            $table->string('status')->nullable(); // Added to store vehicle status (moving, parked, idling, offline)

            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('tracker_id')->references('id')->on('trackers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_locations');
    }
};
