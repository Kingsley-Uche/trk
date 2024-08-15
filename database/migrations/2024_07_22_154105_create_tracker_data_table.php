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
        Schema::create('tracker_data', function (Blueprint $table) {
           $table->id();
            $table->string('device_id');
            $table->string('protocol');
            $table->decimal('lat', 10, 6); // Latitude with precision
            $table->decimal('lng', 10, 6); // Longitude with precision
            $table->integer('altitude')->nullable(); // Use integer for altitude
            $table->integer('angle')->nullable(); // Use integer for angle
            $table->string('ip')->nullable(); // Make IP address nullable
            $table->dateTime('date_tracker')->nullable(); // Use dateTime for tracking dates
            $table->dateTime('date_server')->nullable();
            $table->dateTime('date_last_move')->nullable();
            $table->dateTime('date_last_idle')->nullable();
            $table->dateTime('date_last_stop')->nullable();
            $table->float('speed', 8, 2)->nullable(); // Use float for speed
            $table->float('odometer', 10, 2)->nullable(); // Use float for odometer
            $table->string('odometer_type')->nullable();
            $table->string('sim_no')->nullable();
            $table->json('params')->nullable(); // JSON field for additional parameters
            $table->string('port')->nullable(); // Make port nullable
            $table->string('network_protocol')->nullable();
           
            $table->timestamps();

            // Adding indexes for frequently queried columns
            // $table->index(['device_id']);
            // $table->index(['protocol']);
            // $table->index(['ip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_data');
    }
};
