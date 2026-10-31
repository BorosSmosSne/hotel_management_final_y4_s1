<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Payments: Staff can record only. Edit, delete and refund are Admin only.
 * Auto-discovered (App\Models\Payment -> App\Policies\PaymentPolicy).
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function refund(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
