<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The status column was ENUM('available','occupied','maintenance'). A plain string lets us add
        // "cleaning" (and any future status) without altering the column again.
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('status', 20)->default('available')->change();
        });

        // Who cleaned the room last, and when (the full history is in housekeeping_logs).
        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('last_cleaned_at')->nullable()->after('status');
            $table->foreignId('last_cleaned_by')->nullable()->after('last_cleaned_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_cleaned_by');
            $table->dropColumn('last_cleaned_at');
        });

        DB::table('rooms')->where('status', 'cleaning')->update(['status' => 'available']);

        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available')->change();
        });
    }
};
