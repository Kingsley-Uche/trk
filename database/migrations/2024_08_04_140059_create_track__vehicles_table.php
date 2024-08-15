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
        
        Schema::create('track__vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('tracker_id')->constrained('trackers')->onDelete('cascade');
            $table->string('vehicle_vin');
            $table->string('tracker_imei');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track__vehicles');
    }
};
