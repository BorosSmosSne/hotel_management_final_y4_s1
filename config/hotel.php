<?php

/*
 | Hotel identity used on invoices (and later, the sidebar brand and price calculations).
 | Phase 4 ("Settings") will let an admin edit these values in the app; until then they
 | come from .env, e.g. HOTEL_NAME="Grand Hotel".
 */
return [
    'name'     => env('HOTEL_NAME', 'Grand Hotel'),
    'address'  => env('HOTEL_ADDRESS', ''),
    'phone'    => env('HOTEL_PHONE', ''),
    'email'    => env('HOTEL_EMAIL', ''),
    'currency' => env('HOTEL_CURRENCY', '$'),
    // path relative to public/, e.g. "storage/logo.png" (leave null to show the hotel initial)
    'logo'     => env('HOTEL_LOGO'),

    // First administrator, created by `php artisan db:seed --class=AdminSeeder`.
    // Change these in .env (ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD), then change the password after first login.
    'admin' => [
        'name'     => env('ADMIN_NAME', 'Admin'),
        'email'    => env('ADMIN_EMAIL', 'admin@gmail.com'),
        'password' => env('ADMIN_PASSWORD', 'Ls112233'),
    ],
];
