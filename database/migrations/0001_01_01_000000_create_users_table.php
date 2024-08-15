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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // This creates an unsignedBigInteger primary key
            $table->string('first_name'); // Consider using snake_case
            $table->string('last_name');
            $table->string('middle_name')->nullable(); // Changed to snake_case for consistency
            $table->string('phone');
            $table->string('user_type');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // Foreign key constraint
            $table->string('status');
            $table->string('email')->unique();
            $table->string('api_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('last_activity')->nullable(); //
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Ensure this references the 'users' table correctly
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
