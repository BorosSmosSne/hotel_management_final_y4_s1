<?php

namespace App\Support;

/**
 * Single place that answers "what is the hotel called / what currency do we use?".
 * Today it reads config/hotel.php. In Phase 4 only this class changes (to read the
 * settings table), so invoices keep working untouched.
 */
class HotelSettings
{
    /** @return array{name:string,address:string,phone:string,email:string,currency:string,logo:?string} */
    public static function all(): array
    {
        return [
            'name'     => (string) config('hotel.name', 'Grand Hotel'),
            'address'  => (string) config('hotel.address', ''),
            'phone'    => (string) config('hotel.phone', ''),
            'email'    => (string) config('hotel.email', ''),
            'currency' => (string) config('hotel.currency', '$'),
            'logo'     => config('hotel.logo') ?: null,
        ];
    }

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
