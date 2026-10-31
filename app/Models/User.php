<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** True when this user is an active admin and no OTHER active admin exists. */
    public function isLastActiveAdmin(): bool
    {
        return $this->isAdmin()
            && $this->is_active
            && ! static::where('role', self::ROLE_ADMIN)
                ->where('is_active', true)
                ->whereKeyNot($this->getKey())
                ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ---------- Activity log ---------- */

    protected function activitySubject(): string
    {
        return 'User #' . $this->getKey() . ' (' . $this->name . ')';
    }

    protected function activityCreatedNote(): string
    {
        return 'role ' . $this->role;
    }
}
