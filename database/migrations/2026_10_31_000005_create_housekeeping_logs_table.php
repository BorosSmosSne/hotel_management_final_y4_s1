<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();      // who did it
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete(); // set on check-out
            // checked_out | cleaned | needs_cleaning | maintenance_started | maintenance_ended
            $table->string('action', 30);
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('note', 255)->nullable();
            $table->timestamps();                                                                // created_at = when

            $table->index(['room_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_logs');
    }
};
