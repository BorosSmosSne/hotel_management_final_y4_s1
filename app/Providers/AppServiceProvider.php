<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Room;
use App\Support\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
                $view->with('sidebarCleaning', Room::where('status', 'cleaning')->count());
            } catch (QueryException $e) {
                $view->with('sidebarFloors', collect()); // e.g. migrations not run yet: never break the page
            }
        });

        // @money($amount) in Blade prints the amount with the hotel's currency symbol (Settings).
        Blade::directive('money', fn ($expression) => "<?php echo e(\\App\\Support\\HotelSettings::money({$expression})); ?>");

        // Sign-ins are logged here (not in AuthController) so every way of signing in is covered.
        Event::listen(Login::class, function (Login $e) {
            ActivityLogger::log('login', "{$e->user->name} ({$e->user->email}) signed in", null, $e->user->getAuthIdentifier());
        });
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                ActivityLogger::log('logout', "{$e->user->name} ({$e->user->email}) signed out", null, $e->user->getAuthIdentifier());
            }
        });
        Event::listen(Failed::class, function (Failed $e) {
            // The password is never logged, only the email that was tried.
            ActivityLogger::log('login_failed', 'Failed sign-in attempt for ' . Str::limit((string) ($e->credentials['email'] ?? 'unknown'), 120), null, $e->user?->getAuthIdentifier());
        });
    }
}
