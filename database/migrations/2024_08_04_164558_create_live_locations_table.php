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
        Schema::create('live_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('tracker_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2);
            $table->string('speed_unit')->nullable(); // Store speed units (km/h, mph)
            $table->integer('course');
            $table->timestamp('fix_time');
            $table->integer('satellite_count'); // Store satellite count
            $table->integer('active_satellite_count'); // Store active satellite count
            $table->boolean('real_time_gps');
            $table->boolean('gps_positioned');
            $table->boolean('east_longitude');
            $table->boolean('north_latitude');
            $table->integer('mcc');
            $table->integer('mnc');
            $table->integer('lac');
            $table->integer('cell_id');
            $table->string('serial_number'); // Serial number as a string
            $table->integer('error_check');
            $table->json('event'); // Store event data
            $table->bigInteger('parse_time'); // Changed to bigInteger to accommodate large values
            $table->json('terminal_info')->nullable(); // Store terminal information
            $table->string('voltage_level')->nullable(); // Store voltage level
            $table->string('gsm_signal_strength')->nullable(); // Store GSM signal strength
            $table->json('response_msg')->nullable(); // Store response messages
            $table->string('status')->nullable(); // Store vehicle status (moving, parked, idling, offline)

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
        Schema::dropIfExists('live_locations');
    }
};
