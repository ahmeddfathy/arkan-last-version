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
            $table->id();
            $table->unsignedInteger('employee_id')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();

            $table->string('password');
            $table->string('role')->default('employee');


            $table->string('profile_photo_path', 2048)->nullable();
            $table->integer('age');
            $table->date('date_of_birth');
            $table->string('national_id_number')->unique();
            $table->string('phone_number');
            $table->date('start_date_of_employment') ->nullable();
            $table->date('last_contract_start_date')->nullable();
            $table->date('last_contract_end_date')->nullable();
            $table->string('job_progression')->nullable();
            $table->string('department')->nullable();
            $table->string('gender');
            $table->string('address');
            $table->string('education_level');
            $table->string('marital_status');
            $table->integer('number_of_children')->default(0);
            $table->string('employee_status')->default('active');
            $table->timestamp('email_verified_at')->nullable();

            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
        
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
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
