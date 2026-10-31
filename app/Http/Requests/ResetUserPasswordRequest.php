<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Admin sets a new password for another user (dialog on the Users page). */
class ResetUserPasswordRequest extends FormRequest
{
    // Errors go to their own bag so the dialog can show them without mixing with other forms.
    protected $errorBag = 'resetPassword';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'  => 'Please enter a new password.',
            'password.min'       => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
