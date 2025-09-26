<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RequestPasswordResetRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
    
    public function getEmail(): string
    {
        return $this->validated()['email'];
    }
}