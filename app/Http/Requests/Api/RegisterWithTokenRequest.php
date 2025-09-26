<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\RegisterWithTokenCommand; // Use RegisterWithTokenCommand instead
use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;
use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\ReferralCode;

class RegisterWithTokenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'firstName' => ['required', 'string'],
            'agreedToTerms' => ['required', 'accepted'],
            'registration_token' => ['required', 'string']
        ];
    }

    public function toCommand(): RegisterWithTokenCommand
    {
        $validated = $this->validated();
        return new RegisterWithTokenCommand(
            EmailAddress::fromString($validated['email']),
            PlainTextPassword::fromString($validated['password']),
            $validated['firstName'],
            $validated['lastName'] ?? '',
            !empty($validated['phone']) ? PhoneNumber::fromString($validated['phone']) : null,
            (bool) $validated['agreedToTerms'],
            (bool) ($validated['agreedToMarketing'] ?? false),
            !empty($validated['referralCode']) ? ReferralCode::fromString($validated['referralCode']) : null,
            $validated['registration_token']
        );
    }
}
