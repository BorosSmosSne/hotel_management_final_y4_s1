<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    /** Demo guests. Safe to run more than once (matched by ID number). */
    public function run(): void
    {
        $guests = [
            ['Lucas Thomas',    '+1 555 010 1001', 'lucas.thomas@example.com',    'P1234 5678', 'United States', '12 Main St, Austin'],
            ['James Wilson',    '+880 1711 000 002', 'james.wilson@example.com',  'P2234 5678', 'Bangladesh',    'Dhaka'],
            ['Elijah Garcia',   '+90 532 000 0003', 'elijah.garcia@example.com',  'P3234 5678', 'Türkiye',       'Istanbul'],
            ['Noah Smith',      '+31 6 1000 0004',  'noah.smith@example.com',     'P4234 5678', 'Netherlands',   'Amsterdam'],
            ['Isabella Anderson','+90 532 000 0005', 'isabella.a@example.com',    'P5234 5678', 'Türkiye',       'Ankara'],
        ];

        foreach ($guests as [$name, $phone, $email, $idNumber, $nationality, $address]) {
            Guest::updateOrCreate(['id_number' => $idNumber], compact('name', 'phone', 'email', 'nationality', 'address'));
        }
    }
}
