<?php

namespace App\Commands;

use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PlainTextPassword;

class LoginUserCommand
{
    public function __construct(
        public EmailAddress $email,
        public PlainTextPassword $password
    ) {}
}