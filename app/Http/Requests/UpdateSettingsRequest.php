<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();   // the route is also behind role:admin
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'address'       => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:40'],
            'email'         => ['nullable', 'email', 'max:150'],
            'currency'      => ['required', 'string', 'max:5'],
            'tax_rate'      => ['required', 'numeric', 'min:0', 'max:100'],
            'checkin_time'  => ['required', 'date_format:H:i'],
            'checkout_time' => ['required', 'date_format:H:i'],
            // SVG is deliberately not allowed (it can carry scripts and dompdf handles it poorly)
            'logo'          => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Please enter the hotel name.',
            'currency.required'      => 'Please enter a currency symbol, for example $ or €.',
            'currency.max'           => 'The currency symbol can be at most 5 characters.',
            'tax_rate.min'           => 'Tax cannot be negative.',
            'tax_rate.max'           => 'Tax cannot be more than 100%.',
            'checkin_time.date_format'  => 'Use the time format HH:MM, for example 14:00.',
            'checkout_time.date_format' => 'Use the time format HH:MM, for example 12:00.',
            'logo.mimes'             => 'The logo must be a PNG, JPG or WEBP image.',
            'logo.max'               => 'The logo must be 2 MB or smaller.',
        ];
    }
}
