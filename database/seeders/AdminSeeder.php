<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the FIRST administrator. There is no public registration, so this is how you get in.
 *
 *   php artisan db:seed --class=AdminSeeder
 *
 * Name, email and password come from config/hotel.php  ->  .env:
 *   ADMIN_NAME="Admin"  ADMIN_EMAIL="you@hotel.com"  ADMIN_PASSWORD="a-strong-password"
 *
 * Safe to run again: an existing account is never given a new password, it is only made sure
 * to be an active admin.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('hotel.admin');

        $admin = User::firstOrCreate(
            ['email' => $cfg['email']],
            [
                'name'      => $cfg['name'],
                'password'  => Hash::make($cfg['password']),
                'role'      => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // Already existed (maybe as staff / deactivated): keep its password, make sure it can administer.
        if (! $admin->wasRecentlyCreated && (! $admin->isAdmin() || ! $admin->is_active)) {
            $admin->forceFill(['role' => User::ROLE_ADMIN, 'is_active' => true])->save();
        }

        $this->command?->info(
            $admin->wasRecentlyCreated
                ? "Admin created: {$admin->email}  (change the password after your first login)"
                : "Admin already exists: {$admin->email}  (password left unchanged)"
        );
    }
}
