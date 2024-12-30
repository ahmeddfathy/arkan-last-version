<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_requests_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reason');
            $table->boolean('manager_mistake')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('violations');
    }
};
