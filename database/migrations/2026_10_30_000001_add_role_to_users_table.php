<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles: "admin" (full access) and "staff" (default; limited, see BookingPolicy).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->after('password');
        });

        // Existing installs: make sure somebody is still an administrator.
        DB::table('users')->where('email', 'admin@gmail.com')->update(['role' => 'admin']);

        if (! DB::table('users')->where('role', 'admin')->exists()) {
            $firstId = DB::table('users')->orderBy('id')->value('id');
            if ($firstId) {
                DB::table('users')->where('id', $firstId)->update(['role' => 'admin']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
