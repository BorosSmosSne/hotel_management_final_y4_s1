<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'id_number', 'nationality', 'address', 'notes'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Search by name, phone, ID/passport number or email. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('id_number', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")));
    }
}
