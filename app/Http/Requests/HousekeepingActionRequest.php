<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Housekeeping buttons (Admin + Staff). The only input is an optional note. */
class HousekeepingActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['note.max' => 'The note can be at most 255 characters.'];
    }
}
