<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    public const CONFIRMED   = 'confirmed';
    public const CHECKED_IN  = 'checked_in';
    public const CHECKED_OUT = 'checked_out';
    public const CANCELLED   = 'cancelled';

    protected $fillable = [
        'room_id', 'guest_id', 'created_by',
        'guest_name', 'phone', 'id_number', 'guests', 'notes',
        'check_in', 'check_out', 'nights', 'price_per_night', 'total_price',
        'status', 'checked_in_at', 'checked_out_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in'        => 'date',
            'check_out'       => 'date',
            'price_per_night' => 'decimal:2',
            'total_price'     => 'decimal:2',
            'checked_in_at'   => 'datetime',
            'checked_out_at'  => 'datetime',
            'cancelled_at'    => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------- Scopes ---------- */

    /** Bookings that still hold the room (not cancelled / finished). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::CONFIRMED, self::CHECKED_IN]);
    }

    /** Bookings whose stay includes the night of $date (check-out day is free again). */
    public function scopeCovering(Builder $query, Carbon $date): Builder
    {
        return $query->where('check_in', '<=', $date->toDateString())
                     ->where('check_out', '>', $date->toDateString());
    }

    /**
     * Bookings that overlap [$in, $out). Same-day turnover is allowed:
     * a stay ending on the 15th does not clash with one starting on the 15th.
     */
    public function scopeOverlapping(Builder $query, Carbon $in, Carbon $out): Builder
    {
        return $query->where('check_in', '<', $out->toDateString())
                     ->where('check_out', '>', $in->toDateString());
    }

    /** Adds paid_in / paid_out sums so lists can show payment status without N+1 queries. */
    public function scopeWithPaymentTotals(Builder $query): Builder
    {
        return $query
            ->withSum(['payments as paid_in' => fn ($p) => $p->whereIn('type', [Payment::DEPOSIT, Payment::PAYMENT])], 'amount')
            ->withSum(['payments as paid_out' => fn ($p) => $p->where('type', Payment::REFUND)], 'amount');
    }

    /* ---------- Payments ---------- */

    /** Net money received (deposits + payments - refunds). Optionally ignores one payment (when editing it). */
    public function netPaid(?int $ignorePaymentId = null): float
    {
        // Fast path: totals were preloaded with withPaymentTotals()
        if ($ignorePaymentId === null && array_key_exists('paid_in', $this->attributes)) {
            return round((float) $this->attributes['paid_in'] - (float) ($this->attributes['paid_out'] ?? 0), 2);
        }

        $q = fn () => $this->payments()->when($ignorePaymentId, fn ($x) => $x->whereKeyNot($ignorePaymentId));

        $in  = (float) $q()->whereIn('type', [Payment::DEPOSIT, Payment::PAYMENT])->sum('amount');
        $out = (float) $q()->where('type', Payment::REFUND)->sum('amount');

        return round($in - $out, 2);
    }

    /** What is still owed (never negative). */
    public function balanceDue(?int $ignorePaymentId = null): float
    {
        return max(round((float) $this->total_price - $this->netPaid($ignorePaymentId), 2), 0);
    }

    /** unpaid | partial | paid */
    public function paymentStatus(): string
    {
        $paid = $this->netPaid();

        if ($paid <= 0) {
            return 'unpaid';
        }

        return $paid + 0.001 >= (float) $this->total_price ? 'paid' : 'partial';
    }

    /* ---------- Helpers ---------- */

    /** Number of nights between two dates (minimum 1). */
    public static function nightsBetween(Carbon $in, Carbon $out): int
    {
        return max(1, (int) abs($in->copy()->startOfDay()->diffInDays($out->copy()->startOfDay())));
    }

    /** Guest may be checked in from the arrival date until the night before departure. */
    public function canCheckIn(): bool
    {
        return $this->status === self::CONFIRMED
            && $this->check_in->lte(today())
            && $this->check_out->gt(today());
    }

    public function canCheckOut(): bool
    {
        return $this->status === self::CHECKED_IN;
    }

    public function canCancel(): bool
    {
        return $this->status === self::CONFIRMED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::CONFIRMED, self::CHECKED_IN], true);
    }
}
