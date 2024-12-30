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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id') -> nullable();
            $table->foreign('employee_id')
            ->references('employee_id')
            ->on('users')
            ->onDelete('cascade');
            $table->date('attendance_date')  -> nullable();
            $table->string('day')  -> nullable();
            $table->string('status')  -> nullable();
            $table->string('shift')  -> nullable();
            $table->integer('shift_hours')  -> nullable();
            $table->time('entry_time')  -> nullable();
            $table->time('exit_time')  -> nullable();
            $table->integer('delay_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->integer('working_hours')  -> nullable();
            $table->integer('overtime_hours')->default(0);
            $table->string('penalty')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
