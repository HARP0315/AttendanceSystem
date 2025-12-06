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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->string('reason',255)->nullable();
            $table->tinyInteger('status'); // 1:出勤中, 2: 休憩中, 3: 退勤済み
            $table->tinyInteger('is_deleted')->default(0); //1:削除済み
            $table->unique(['user_id', 'work_date','is_deleted']);
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
