<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tax is a SNAPSHOT per booking, like price_per_night: changing the tax % in Settings
     * never alters an existing booking or its invoice.
     *
     * total_price stays the grand total (room charge + tax), so payments, balances and
     * "paid / partial / unpaid" keep working untouched.
     * Existing bookings get 0%, which keeps their totals exactly as they were.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('price_per_night');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }
};
