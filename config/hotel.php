<?php

/*
 | Hotel defaults.
 |
 | Phase 4: the hotel's name, address, phone, email, currency, logo, tax % and default
 | check-in / check-out times are now edited in the app (Admin > Settings) and stored in the
 | `settings` table. App\Support\HotelSettings reads the table first and falls back to the values
 | below for anything that has not been saved yet, so a fresh install still shows a sensible hotel.
 | Once a value is saved in Settings, the matching HOTEL_* line in .env is no longer used.
 */
return [
    'name'     => env('HOTEL_NAME', 'Grand Hotel'),
    'address'  => env('HOTEL_ADDRESS', ''),
    'phone'    => env('HOTEL_PHONE', ''),
    'email'    => env('HOTEL_EMAIL', ''),
    'currency' => env('HOTEL_CURRENCY', '$'),
    // path relative to public/, e.g. "uploads/logo.png" (leave null to show the hotel initial)
    'logo'     => env('HOTEL_LOGO'),

    // Tax % added to the room price of NEW bookings (0 = no tax), and default times shown on bookings / invoices.
    'tax_rate'      => (float) env('HOTEL_TAX_RATE', 0),
    'checkin_time'  => env('HOTEL_CHECKIN_TIME', '14:00'),
    'checkout_time' => env('HOTEL_CHECKOUT_TIME', '12:00'),

    // First administrator, created by `php artisan db:seed --class=AdminSeeder`.
    // Change these in .env (ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD), then change the password after first login.
    'admin' => [
        'name'     => env('ADMIN_NAME', 'Admin'),
        'email'    => env('ADMIN_EMAIL', 'admin@gmail.com'),
        'password' => env('ADMIN_PASSWORD', 'Ls112233'),
    ],
];
