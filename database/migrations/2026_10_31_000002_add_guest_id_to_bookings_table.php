<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Deleting a guest keeps the booking (it still has its own name/phone/ID snapshot).
            $table->foreignId('guest_id')->nullable()->after('room_id')->constrained('guests')->nullOnDelete();
        });

        // Link bookings that already exist: one guest per distinct ID/passport number.
        DB::table('bookings')->whereNull('guest_id')->orderBy('id')->get()->each(function ($b) {
            $guestId = DB::table('guests')->where('id_number', $b->id_number)->value('id');

            if (! $guestId) {
                $guestId = DB::table('guests')->insertGetId([
                    'name'       => $b->guest_name,
                    'phone'      => $b->phone,
                    'id_number'  => $b->id_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('bookings')->where('id', $b->id)->update(['guest_id' => $guestId]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
        });
    }
};
