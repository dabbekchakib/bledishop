<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Customer details are validated here; the amount the customer pays is
     * always recomputed server-side from the catalog (see OrderService).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $createAccount = filter_var($this->input('create_account'), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:160'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'create_account' => ['sometimes', 'boolean'],
        ];

        if ($createAccount) {
            $rules['password'] = ['required', 'confirmed', 'min:8', 'max:255'];
        }

        return $rules;
    }

    /**
     * Custom friendly messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => __('checkout.validation.first_name_required'),
            'last_name.required' => __('checkout.validation.last_name_required'),
            'phone.required' => __('checkout.validation.phone_required'),
            'email.required' => __('checkout.validation.email_required'),
            'email.email' => __('checkout.validation.email_invalid'),
            'address.required' => __('checkout.validation.address_required'),
            'password.required_if' => __('checkout.validation.password_required'),
            'password.confirmed' => __('checkout.validation.password_confirmed'),
            'password.min' => __('checkout.validation.password_min'),
        ];
    }
}
