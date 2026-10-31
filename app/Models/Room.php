<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory, LogsActivity;

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

    /**
     * Distinct floors from the rooms table with room counts, for the sidebar floors menu.
     * "available" uses the same rule as statusOn(): status "available" and no active booking covering the day.
     *
     * @return \Illuminate\Support\Collection<int, array{floor:int,total:int,available:int}>
     */
    public static function floorSummary(?\Carbon\Carbon $date = null): \Illuminate\Support\Collection
    {
        $date ??= today();
        $booked = Booking::active()->covering($date)->pluck('room_id')->flip();

        return static::query()->get(['id', 'floor', 'status'])
            ->groupBy('floor')
            ->sortKeys()
            ->map(fn ($rooms, $floor) => [
                'floor'     => (int) $floor,
                'total'     => $rooms->count(),
                'available' => $rooms->filter(fn ($r) => $r->status === 'available' && ! $booked->has($r->id))->count(),
            ])
            ->values();
    }

    protected function casts(): array
    {
        return ['last_cleaned_at' => 'datetime'];
    }

    public function lastCleaner(){
        return $this->belongsTo(User::class, 'last_cleaned_by');
    }

    public function housekeepingLogs(){
        return $this->hasMany(HousekeepingLog::class);
    }

    public function bookings(){
        return $this->hasMany(Booking::class);
    }

    /**
     * Effective status for the booking screen, as of one day.
     *
     * $current is the active booking whose stay covers that day (or null).
     * Priority: maintenance > cleaning > checked-in guest > manually "occupied" > reserved > available.
     *
     * @return array{key:string,label:string,reason:?string}
     */
    public function statusOn(?Booking $current): array
    {
        if ($this->status === 'maintenance') {
            return ['key' => 'maintenance', 'label' => 'Maintenance',
                'reason' => 'Under maintenance or cleaning - it cannot be booked until it is back in service.'];
        }

        if ($this->status === 'cleaning') {
            return ['key' => 'cleaning', 'label' => 'Cleaning',
                'reason' => 'Being cleaned - housekeeping must mark it clean before it can be booked.'];
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

    /* ---------- Activity log ---------- */

    protected function activitySubject(): string
    {
        return 'Room ' . $this->room_number;
    }

    /** Cleaning / maintenance changes are already recorded, with more detail, in Housekeeping's own log. */
    protected function activityIgnored(): array
    {
        return ['status', 'last_cleaned_at', 'last_cleaned_by'];
    }

    protected function activityCreatedNote(): string
    {
        return 'floor ' . $this->floor;
    }
}
