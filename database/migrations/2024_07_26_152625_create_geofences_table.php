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
        Schema::create('geofences', function (Blueprint $table) {
            
            $table->id();
            $table->string('name')->unique(); // Ensure the name is unique
            $table->foreignId('created_by_user_id')
                ->constrained('users') // Specify the related table name if different from default
                ->onDelete('cascade');
            $table->foreignId('vehicle_id')
                ->constrained('vehicles') // Specify the related table name if different from default
                ->onDelete('cascade');
                $table->geometry('area', 'polygon');// Define 'polygon' as the type correctly
            $table->timestamps();
            $table->spatialIndex('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
