<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class ResetToken
{
    public function __construct(public readonly string $value) 
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Reset token cannot be empty');
        }
    }

    public static function fromString(string $token): self
    {
        return new self($token);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}