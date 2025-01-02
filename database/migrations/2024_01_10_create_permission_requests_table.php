<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('departure_time');
            $table->dateTime('return_time');
            $table->boolean('returned_on_time')->default(true);
            $table->integer('minutes_used');
            $table->integer('remaining_minutes');
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->enum('manager_status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('manager_rejection_reason')->nullable();
            $table->enum('hr_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('hr_rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_requests');
    }
};
