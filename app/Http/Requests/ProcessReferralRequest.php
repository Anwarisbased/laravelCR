<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessReferralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller for more complex logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'referral_code' => 'required|string|exists:users,referral_code|max:20',
        ];
    }
    
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'referral_code.required' => 'A referral code is required.',
            'referral_code.string' => 'The referral code must be a valid string.',
            'referral_code.exists' => 'The referral code does not exist.',
            'referral_code.max' => 'The referral code is too long.',
        ];
    }
}
