<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PerformPasswordResetRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }
    
    public function getEmail(): string
    {
        return $this->validated()['email'];
    }
    
    public function getToken(): string
    {
        return $this->validated()['token'];
    }
    
    public function getPassword(): string
    {
        return $this->validated()['password'];
    }
}