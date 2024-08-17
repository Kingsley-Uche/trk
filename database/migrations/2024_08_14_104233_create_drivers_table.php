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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('vehicle_vin');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('pin');
            $table->string('country');
            $table->string('licence_number');
            $table->date('licence_issue_date');
            $table->date('licence_expiry_date');
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('profile_picture_path')->nullable();
            $table->string('driving_licence_path')->nullable();
            $table->string('pin_path')->nullable();
            $table->string('miscellaneous_path')->nullable();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
