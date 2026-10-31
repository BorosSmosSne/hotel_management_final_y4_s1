<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\HotelSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    public const DEPOSIT = 'deposit';
    public const PAYMENT = 'payment';
    public const REFUND  = 'refund';

    public const TYPES = [self::DEPOSIT => 'Deposit', self::PAYMENT => 'Payment', self::REFUND => 'Refund'];

    public const METHODS = [
        'cash'          => 'Cash',
        'card'          => 'Card',
        'bank_transfer' => 'Bank transfer',
        'qr'            => 'QR',
    ];

    protected $fillable = ['booking_id', 'amount', 'method', 'type', 'reference', 'received_by', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isRefund(): bool
    {
        return $this->type === self::REFUND;
    }

    /** Money in (+) or out (-): refunds are negative. */
    public function signedAmount(): float
    {
        return $this->isRefund() ? -(float) $this->amount : (float) $this->amount;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /* ---------- Activity log ---------- */

    protected function activitySubject(): string
    {
        return 'Payment #' . $this->getKey() . ' (' . $this->typeLabel() . ' ' . HotelSettings::money($this->amount)
            . ', booking #' . $this->booking_id . ')';
    }

    protected function activityCreatedNote(): string
    {
        return $this->methodLabel() . ($this->reference ? ', ref ' . $this->reference : '');
    }
}
