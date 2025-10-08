<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\LoginUserCommand;
use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;

class LoginRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function toCommand(): LoginUserCommand
    {
        $validated = $this->validated();
        return new LoginUserCommand(
            EmailAddress::fromString($validated['email']),
            PlainTextPassword::fromString($validated['password'])
        );
    }
}