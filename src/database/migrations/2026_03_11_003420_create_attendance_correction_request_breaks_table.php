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
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_request_id')->constrained('attendance_correction_requests')->name('acr_breaks_request_fk');
            $table->foreignId('attendance_break_id')->nullable()->constrained();
            $table->dateTime('requested_break_start');
            $table->dateTime('requested_break_end');
            $table->unsignedInteger('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
};
