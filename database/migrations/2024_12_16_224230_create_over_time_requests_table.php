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
        Schema::create('over_time_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('overtime_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('reason');

            // حالة المدير
            $table->enum('manager_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('manager_rejection_reason')->nullable();

            // حالة HR
            $table->enum('hr_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('hr_rejection_reason')->nullable();

            // الحالة النهائية
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();

            // إضافة فهرس للبحث السريع
            $table->index(['user_id', 'overtime_date']);
            $table->index(['status', 'manager_status', 'hr_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('over_time_requests');
    }
};
