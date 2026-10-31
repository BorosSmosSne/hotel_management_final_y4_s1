<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only history of room cleaning / maintenance changes. */
class HousekeepingLog extends Model
{
    public const ACTIONS = [
        'checked_out'         => 'Guest checked out, needs cleaning',
        'cleaned'             => 'Cleaned',
        'needs_cleaning'      => 'Marked for cleaning',
        'maintenance_started' => 'Sent to maintenance',
        'maintenance_ended'   => 'Back in service',
    ];

    protected $fillable = ['room_id', 'user_id', 'booking_id', 'action', 'from_status', 'to_status', 'note'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}
