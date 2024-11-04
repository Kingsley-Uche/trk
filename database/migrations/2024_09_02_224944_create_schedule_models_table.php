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
        Schema::create('schedule_models', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('description')->nullable(); // Optional string field, renamed for clarity
            $table->string('vehicle_vin'); // VIN of the vehicle, must be validated against vehicles table
            $table->enum('schedule_type', ['custom', 'recurring']); // Defines type of schedule
            $table->integer('no_time')->nullable(); // Number of times, optional
            $table->integer('no_kilometer')->nullable(); // Kilometers, optional
            $table->integer('no_hours')->nullable(); // Hours, optional
            $table->enum('category_time', ['days', 'weeks', 'months', 'years'])->nullable(); // Time category
            $table->integer('reminder_advance_days')->nullable(); // Reminder days
            $table->integer('reminder_advance_km')->nullable(); // Reminder kilometers
            $table->integer('reminder_advance_hr')->nullable(); // Reminder hours
            $table->date('start_date')->nullable(); // Start date, relevant for recurring schedules
            $table->timestamps(); // Created at and updated at timestamps

            // Foreign key constraints
            $table->foreign('vehicle_vin')->references('vin')->on('vehicles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_models'); // Drop the schedule_models table if it exists
    }
};