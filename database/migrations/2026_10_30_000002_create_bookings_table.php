<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Guest details
            $table->string('guest_name');
            $table->string('phone', 30);
            $table->string('id_number', 50);          // ID card / passport number
            $table->unsignedInteger('guests')->default(1);
            $table->text('notes')->nullable();

            // Stay + pricing (price is a snapshot, so later rate changes never alter old bookings)
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('total_price', 10, 2);

            // confirmed -> checked_in -> checked_out   (or confirmed -> cancelled)
            $table->string('status', 20)->default('confirmed')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Speeds up the double-booking check
            $table->index(['room_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
