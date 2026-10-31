<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create + edit a guest (Admin and Staff). */
class SaveGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $guest = $this->route('guest');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{6,30}$/'],
            'email'       => ['nullable', 'email', 'max:255'],
            'id_number'   => ['required', 'string', 'max:50', Rule::unique('guests', 'id_number')->ignore($guest?->id)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:500'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Please enter the guest name.',
            'phone.required'     => 'Please enter a phone number.',
            'phone.regex'        => 'The phone number may only contain digits, spaces, + ( ) - .',
            'email.email'        => 'Please enter a valid email address.',
            'id_number.required' => 'Please enter the ID / passport number.',
            'id_number.unique'   => 'A guest with this ID / passport number already exists.',
        ];
    }
}
