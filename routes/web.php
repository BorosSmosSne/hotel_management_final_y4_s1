<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// CHANGED: home now goes to the dashboard (was room-types.index)
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


// Guest-only routes
Route::middleware('guest')->group(function(){
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class,'register']);
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->name('login.submit');
});

Route::post('/logout',[AuthController::class, 'logout'])
->middleware('auth')
->name('logout');


// Protected app routes
Route::middleware('auth')->group(function(){
    // NEW
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('room-types', RoomTypeController::class);
    Route::resource('rooms',RoomController::class);
});
