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
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained();
            $table->foreignId('request_user_id')->constrained('users');
            $table->dateTime('requested_clock_in');
            $table->dateTime('requested_clock_out');
            $table->string('request_remarks', 255);
            $table->string('status', 20);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};
