<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating AND editing a user (Admin only).
 * On create there is no {user} route parameter; on edit there is, so the same rules adapt.
 */
class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Second line of defence: the routes are also behind the role:admin middleware.
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone'    => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{6,30}$/'],
            'role'     => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
            // required when creating; optional when editing (blank keeps the current password)
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Please enter the full name.',
            'email.required'     => 'Please enter an email address.',
            'email.email'        => 'Please enter a valid email address.',
            'email.unique'       => 'This email address is already used by another user.',
            'phone.regex'        => 'The phone number may only contain digits, spaces, + ( ) - .',
            'role.in'            => 'Please choose Admin or Staff.',
            'status.in'          => 'Please choose Active or Inactive.',
            'password.required'  => 'Please enter a password.',
            'password.min'       => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
