<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'floor',
        'status',
        'description',
        'room_type_id',
    ];

    public function roomType(){
        return $this->belongsTo(RoomType::class);
    }

    public function bookings(){
        return $this->hasMany(Booking::class);
    }

    /**
     * Effective status for the booking screen, as of one day.
     *
     * $current is the active booking whose stay covers that day (or null).
     * Priority: maintenance > checked-in guest > manually "occupied" > reserved > available.
     *
     * @return array{key:string,label:string,reason:?string}
     */
    public function statusOn(?Booking $current): array
    {
        if ($this->status === 'maintenance') {
            return ['key' => 'maintenance', 'label' => 'Maintenance',
                'reason' => 'Under maintenance or cleaning - it cannot be booked until it is back in service.'];
        }

        if ($current && $current->status === Booking::CHECKED_IN) {
            return ['key' => 'occupied', 'label' => 'Occupied',
                'reason' => "Occupied - {$current->guest_name} is staying until {$current->check_out->format('d M Y')}."];
        }

        if ($this->status === 'occupied') {
            return ['key' => 'occupied', 'label' => 'Occupied',
                'reason' => 'Marked as occupied in the room inventory.'];
        }

        if ($current) {
            return ['key' => 'reserved', 'label' => 'Reserved',
                'reason' => "Reserved for {$current->guest_name} ({$current->check_in->format('d M')} - {$current->check_out->format('d M')})."];
        }

        return ['key' => 'available', 'label' => 'Available', 'reason' => null];
    }
}
