<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Support\ActivityLogger;
use App\Support\HotelSettings;
use Illuminate\Support\Str;

/** Hotel settings (Admin only, via role:admin on the routes). */
class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'settings' => HotelSettings::all(),
            'logo'     => HotelSettings::logoSrc(),
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();
        $old  = HotelSettings::all();

        $new = [
            'name'          => trim($data['name']),
            'address'       => trim((string) ($data['address'] ?? '')),
            'phone'         => trim((string) ($data['phone'] ?? '')),
            'email'         => trim((string) ($data['email'] ?? '')),
            'currency'      => trim($data['currency']),
            'tax_rate'      => round((float) $data['tax_rate'], 2),
            'checkin_time'  => $data['checkin_time'],
            'checkout_time' => $data['checkout_time'],
        ];

        // Logo: a new file replaces the old one; "remove" clears it.
        $logoChanged = false;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $name = 'logo-' . Str::lower(Str::random(10)) . '.' . $file->extension();
            $file->move(public_path('uploads'), $name);
            $new['logo'] = 'uploads/' . $name;
            $this->deleteLogoFile($old['logo']);
            $logoChanged = true;
        } elseif ($request->boolean('remove_logo') && $old['logo']) {
            $new['logo'] = null;
            $this->deleteLogoFile($old['logo']);
            $logoChanged = true;
        }

        HotelSettings::save($new);

        $changes = [];
        foreach (['name', 'address', 'phone', 'email', 'currency', 'tax_rate', 'checkin_time', 'checkout_time'] as $key) {
            if ((string) $old[$key] !== (string) $new[$key]) {
                $changes[] = str_replace('_', ' ', $key) . ': ' . ($old[$key] === '' ? '—' : $old[$key]) . ' → ' . ($new[$key] === '' ? '—' : $new[$key]);
            }
        }
        if ($logoChanged) {
            $changes[] = 'logo ' . ($new['logo'] ?? null ? 'replaced' : 'removed');
        }
        ActivityLogger::log('updated', 'Hotel settings updated' . ($changes ? ': ' . implode('; ', $changes) : ' (no changes)'));

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }

    /** Only ever deletes files this screen created (public/uploads/logo-*), never anything else. */
    private function deleteLogoFile(?string $path): void
    {
        if ($path && Str::startsWith($path, 'uploads/logo-')) {
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }
}
