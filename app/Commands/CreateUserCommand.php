<?php
namespace App\Commands;

use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;
use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\ReferralCode;

/**
 * Command DTO for creating a new user.
 * It now requires a validated EmailAddress Value Object.
 */
final class CreateUserCommand {
    public EmailAddress $email;
    public PlainTextPassword $password;
    public string $firstName;
    public string $lastName;
    public ?PhoneNumber $phone;
    public bool $agreedToTerms;
    public bool $agreedToMarketing;
    public ?ReferralCode $referralCode;

    public function __construct(
        EmailAddress $email,
        PlainTextPassword $password,
        string $firstName,
        string $lastName,
        ?PhoneNumber $phone,
        bool $agreedToTerms,
        bool $agreedToMarketing,
        ?ReferralCode $referralCode
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->agreedToTerms = $agreedToTerms;
        $this->agreedToMarketing = $agreedToMarketing;
        $this->referralCode = $referralCode;
    }
}