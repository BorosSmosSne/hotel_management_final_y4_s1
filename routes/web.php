<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// CHANGED: home now goes to the dashboard (was room-types.index)
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


// Guest-only routes. There is NO public registration: users are created by an admin at /users/create.
Route::middleware('guest')->group(function(){
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->name('login.submit');
});

Route::post('/logout',[AuthController::class, 'logout'])
->middleware('auth')
->name('logout');


// Protected app routes
Route::middleware(['auth', 'active'])->group(function(){
    // NEW
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('room-types', RoomTypeController::class);
    Route::resource('rooms',RoomController::class);

    // Room Booking. Role rules live in App\Policies\BookingPolicy and are enforced in the controller.
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->whereNumber('booking')->name('bookings.show');
    Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
    Route::post('/bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

    // Guests: Admin + Staff (delete is Admin only: see GuestController::middleware()).
    Route::get('/guests/search', [GuestController::class, 'search'])->name('guests.search');   // JSON, must come before {guest}
    Route::resource('guests', GuestController::class);

    // Payments: Staff can list + record. Edit / delete / refund are Admin only (SavePaymentRequest + PaymentPolicy).
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store'])->name('bookings.payments.store');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Printable invoice / receipt + PDF
    Route::get('/bookings/{booking}/invoice', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/bookings/{booking}/invoice/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Housekeeping: Admin + Staff. Every change goes through HousekeepingService and is logged.
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
    Route::post('/housekeeping/rooms/{room}/clean', [HousekeepingController::class, 'clean'])->name('housekeeping.clean');
    Route::post('/housekeeping/rooms/{room}/dirty', [HousekeepingController::class, 'dirty'])->name('housekeeping.dirty');
    Route::post('/housekeeping/rooms/{room}/maintenance', [HousekeepingController::class, 'maintenance'])->name('housekeeping.maintenance');
    Route::post('/housekeeping/rooms/{room}/service', [HousekeepingController::class, 'service'])->name('housekeeping.service');

    // My Profile: every signed-in user (Admin + Staff), always their own account.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // User management: ADMIN ONLY. Staff who open /users get a 403 page.
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::put('/{user}/password', [UserController::class, 'resetPassword'])->name('password');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Reports, Activity Log and Settings: ADMIN ONLY. Staff who open these get a 403 page.
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');   // read-only

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
