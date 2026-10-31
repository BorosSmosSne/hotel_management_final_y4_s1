<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * OPTIONAL demo staff login (staff@gmail.com / Ls112233). Safe to run more than once.
     * The real administrator is created by AdminSeeder.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'staff@gmail.com'], [
            'name'      => 'Staff',
            'phone'     => '+855 12 000 002',
            'password'  => Hash::make('Ls112233'),
            'role'      => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }
}
