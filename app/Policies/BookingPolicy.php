<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Who may do what with bookings.
 *
 *  Admin : everything (create, check in/out, edit, cancel, delete)
 *  Staff : create bookings and check guests in / out only
 *
 * Auto-discovered by Laravel (App\Models\Booking -> App\Policies\BookingPolicy).
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        return true;
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }
}
