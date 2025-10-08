<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;

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
    
    public function getEmail(): EmailAddress
    {
        return EmailAddress::fromString($this->validated()['email']);
    }
    
    public function getToken(): string
    {
        return $this->validated()['token'];
    }
    
    public function getNewPassword(): PlainTextPassword
    {
        return PlainTextPassword::fromString($this->validated()['password']);
    }
}