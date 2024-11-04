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
        Schema::create('speed_models', function (Blueprint $table) {
            $table->id();
            $table->decimal('speed_limit', 6, 2); // Define precision for speed limit
            $table->string('vehicle_vin'); // Define VIN as a string
            $table->foreign('vehicle_vin')
                  ->references('vin')
                  ->on('vehicles')
                  ->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speed_models');
    }
};
