<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id') -> nullable();
            $table->foreign('employee_id')
            ->references('employee_id')
            ->on('users');
            $table->string('month');
            $table->string('file_path');
            $table->string('original_filename');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_sheets');
    }
};
