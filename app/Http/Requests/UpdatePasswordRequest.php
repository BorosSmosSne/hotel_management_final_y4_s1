<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** "My Profile": change own password. The current password is required. */
class UpdatePasswordRequest extends FormRequest
{
    // Separate error bag, because the profile page has two forms.
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'Please enter your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required'                 => 'Please enter a new password.',
            'password.min'                      => 'The new password must be at least 8 characters.',
            'password.confirmed'                => 'The password confirmation does not match.',
            'password.different'                => 'The new password must be different from the current one.',
        ];
    }
}
