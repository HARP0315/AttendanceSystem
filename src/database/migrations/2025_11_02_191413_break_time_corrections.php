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
        Schema::create('break_time_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('correction_request_id')->constrained()->cascadeOnDelete();
            $table->time('break_start_time');
            $table->time('break_end_time');
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
