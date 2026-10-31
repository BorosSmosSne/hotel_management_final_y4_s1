<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Single place that answers "what is the hotel called / what currency / what tax rate?".
 *
 * Phase 4: values now live in the `settings` table (edited at Admin > Settings).
 * config/hotel.php (.env) is only the DEFAULT used until a value has been saved, so a fresh
 * install, or one where the migration has not run yet, keeps working exactly as before.
 *
 * Everything that already called HotelSettings::all() / logoSrc() (invoices) keeps working.
 */
class HotelSettings
{
    public const KEYS = [
        'name', 'address', 'phone', 'email', 'currency', 'logo',
        'tax_rate', 'checkin_time', 'checkout_time',
    ];

    private const CACHE_KEY = 'hotel_settings';

    /** Per-request copy, so a page that formats 50 prices does not hit the cache 50 times. */
    private static ?array $memo = null;

    /** @return array{name:string,address:string,phone:string,email:string,currency:string,logo:?string,tax_rate:float,checkin_time:string,checkout_time:string} */
    public static function defaults(): array
    {
        return [
            'name'          => (string) config('hotel.name', 'Grand Hotel'),
            'address'       => (string) config('hotel.address', ''),
            'phone'         => (string) config('hotel.phone', ''),
            'email'         => (string) config('hotel.email', ''),
            'currency'      => (string) config('hotel.currency', '$'),
            'logo'          => config('hotel.logo') ?: null,
            'tax_rate'      => (float) config('hotel.tax_rate', 0),
            'checkin_time'  => (string) config('hotel.checkin_time', '14:00'),
            'checkout_time' => (string) config('hotel.checkout_time', '12:00'),
        ];
    }

    /** @return array{name:string,address:string,phone:string,email:string,currency:string,logo:?string,tax_rate:float,checkin_time:string,checkout_time:string} */
    public static function all(): array
    {
        if (static::$memo !== null) {
            return static::$memo;
        }

        $values = static::defaults();

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());

            foreach (self::KEYS as $key) {
                if (! array_key_exists($key, $stored)) {
                    continue;   // never saved: keep the default
                }
                $values[$key] = match ($key) {
                    'logo'     => $stored[$key] ?: null,
                    'tax_rate' => (float) $stored[$key],
                    default    => (string) $stored[$key],
                };
            }
        } catch (Throwable) {
            // settings table missing (migrations not run yet) or cache unavailable: use the defaults
        }

        return static::$memo = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all()[$key] ?? $default;
    }

    /** Saves the given keys (unknown keys are ignored) and clears the cache. */
    public static function save(array $values): void
    {
        foreach (array_intersect_key($values, array_flip(self::KEYS)) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        static::flush();
    }

    public static function flush(): void
    {
        static::$memo = null;
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            //
        }
    }

    /* ---------- Money / tax / times ---------- */

    public static function currency(): string
    {
        return static::all()['currency'];
    }

    /** "$1,250.00" using the hotel's currency symbol. */
    public static function money(float|int|string|null $amount): string
    {
        return static::currency() . number_format((float) $amount, 2);
    }

    public static function taxRate(): float
    {
        return max(0.0, min(100.0, (float) static::all()['tax_rate']));
    }

    /** "14:00" as stored (24h) */
    public static function checkInTime(): string
    {
        return static::all()['checkin_time'];
    }

    public static function checkOutTime(): string
    {
        return static::all()['checkout_time'];
    }

    /** "14:00" -> "2:00 PM" (falls back to the raw text if it is not a time). */
    public static function formatTime(string $hhmm): string
    {
        $t = \DateTime::createFromFormat('H:i', $hhmm);

        return $t ? $t->format('g:i A') : $hhmm;
    }

    /* ---------- Logo ---------- */

    /** Logo as a URL (screen) or an embedded data URI (PDF), or null when there is none. */
    public static function logoSrc(bool $forPdf = false): ?string
    {
        $logo = static::all()['logo'];
        if (! $logo) {
            return null;
        }

        $path = public_path(ltrim($logo, '/'));
        if (! is_file($path)) {
            return null;
        }

        if ($forPdf) {
            $mime = mime_content_type($path) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
        }

        return asset(ltrim($logo, '/'));
    }
}
