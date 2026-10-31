<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record (Admin + Staff) or edit (Admin only) a payment.
 *
 *  - Staff may record deposits and payments.
 *  - Refunds, and editing an existing payment, are Admin only: authorize() returns false -> 403.
 */
class SavePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Editing an existing payment
        if ($this->route('payment')) {
            return $user->isAdmin();
        }

        // Recording a refund
        if ($this->input('type') === Payment::REFUND) {
            return $user->isAdmin();
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'type'      => ['required', Rule::in(array_keys(Payment::TYPES))],
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'method'    => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at'   => ['nullable', 'date'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'         => 'Please choose Deposit, Payment or Refund.',
            'amount.required' => 'Please enter the amount.',
            'amount.numeric'  => 'The amount must be a number.',
            'amount.min'      => 'The amount must be greater than zero.',
            'method.in'       => 'Please choose Cash, Card, Bank transfer or QR.',
            'paid_at.date'    => 'Please enter a valid date and time.',
        ];
    }
}
