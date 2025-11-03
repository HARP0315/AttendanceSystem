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
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('break_time_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('reason',255)->nullable();
            $table->tinyInteger('request_status'); // 0:承認待ち, 1: 承認済み,
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
