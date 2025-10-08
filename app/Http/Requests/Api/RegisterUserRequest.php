<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\CreateUserCommand;
use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;
use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\ReferralCode;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'firstName' => ['required', 'string'],
            'agreedToTerms' => ['required', 'accepted'],
        ];
    }

    public function toCommand(): CreateUserCommand
    {
        $validated = $this->validated();
        return new CreateUserCommand(
            EmailAddress::fromString($validated['email']),
            PlainTextPassword::fromString($validated['password']),
            $validated['firstName'],
            $validated['lastName'] ?? '',
            !empty($validated['phone']) ? PhoneNumber::fromString($validated['phone']) : null,
            (bool) $validated['agreedToTerms'],
            (bool) ($validated['agreedToMarketing'] ?? false),
            !empty($validated['referralCode']) ? ReferralCode::fromString($validated['referralCode']) : null
        );
    }
}
