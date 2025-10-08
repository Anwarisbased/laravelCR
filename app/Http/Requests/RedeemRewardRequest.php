<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled separately in the policy
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'shipping_details' => 'required|array',
            'shipping_details.first_name' => 'required|string|max:255',
            'shipping_details.last_name' => 'required|string|max:255',
            'shipping_details.address_1' => 'required|string|max:255',
            'shipping_details.address_2' => 'nullable|string|max:255',
            'shipping_details.city' => 'required|string|max:255',
            'shipping_details.state' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
            'shipping_details.postcode' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
            'shipping_details.country' => 'nullable|string|max:255',
            'shipping_details.phone' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_details.state.regex' => 'The state must be a valid 2-letter US state abbreviation.',
            'shipping_details.postcode.regex' => 'The postcode must be a valid US ZIP code format (e.g., 12345 or 12345-6789).',
        ];
    }
}