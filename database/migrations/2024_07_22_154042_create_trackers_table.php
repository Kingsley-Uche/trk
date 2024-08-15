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
        Schema::create('trackers', function (Blueprint $table) {


            
            $table->id();
            $table->string('device_id')->unique();
            $table->string('protocol');
            $table->string('ip');
            $table->string('sim_no');
            $table->json('params')->nullable();
            $table->string('port');
            $table->string('network_protocol');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('vehicle_vin'); // Define the column type explicitly
            $table->foreign('vehicle_vin')->references('vin')->on('vehicles')->onDelete('cascade'); // Add the foreign key constraint
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackers');
    }
};
