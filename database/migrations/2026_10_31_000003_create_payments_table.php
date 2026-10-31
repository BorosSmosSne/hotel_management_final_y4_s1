<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);                 // always positive; "type" decides the direction
            $table->string('method', 20);                     // cash | card | bank_transfer | qr
            $table->string('type', 20);                       // deposit | payment | refund
            $table->string('reference', 100)->nullable();     // receipt / transaction number
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'type']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
