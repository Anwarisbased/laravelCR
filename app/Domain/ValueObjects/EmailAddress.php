<?php
namespace App\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

// A Value Object that guarantees it holds a validly formatted email string.
final class EmailAddress implements JsonSerializable {
    private function __construct(public readonly string $value) {} // private constructor with promoted property

    public static function fromString(string $email): self {
        // Use PHP's built-in validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address provided.");
        }
        return new self(strtolower(trim($email)));
    }

    public function __toString(): string {
        return $this->value;
    }

    public function jsonSerialize(): string {
        return $this->value;
    }
}