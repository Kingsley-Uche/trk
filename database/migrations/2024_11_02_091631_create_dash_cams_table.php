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
        Schema::create('dash_cams', function (Blueprint $table) {
            $table->id();
            $table->string('deviceName');    // For storing device name
            $table->string('deviceID');      // For storing device ID
            $table->string('deviceType');    // For storing device type
            $table->string('channelName');   // For storing channel name
            $table->string('nodeGu');        // For storing nodeGu
            $table->string('sim');           // For storing SIM number
            
            // Using foreignId to define the foreign key constraint
            $table->string('vehicle_id');
            
            // Define vehicle_vin and foreign key reference
            $table->string('vehicle_vin'); // Define the column type explicitly
            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dash_cams');
    }
};
