<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\ValueObjects\EmailAddress;

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
    
    public function getEmail(): EmailAddress
    {
        return EmailAddress::fromString($this->validated()['email']);
    }
}