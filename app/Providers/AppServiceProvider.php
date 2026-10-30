<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Room;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paginator::useBootstrapFive();
                Paginator::defaultView('vendor.pagination.grand');

        // Sidebar "Room Booking > floors" submenu: floors come from the rooms table, never hard-coded.
        View::composer('layouts.app', function ($view) {
            if (! Auth::check()) {
                return;
            }

            try {
                $view->with('sidebarFloors', Room::floorSummary());
            } catch (QueryException $e) {
                $view->with('sidebarFloors', collect()); // e.g. migrations not run yet: never break the page
            }
        });

    }
}
