<?php
namespace App\Policies;

use App\Domain\ValueObjects\EmailAddress;
use App\Infrastructure\WordPressApiWrapperInterface;
use Exception;

class EmailAddressMustBeUniquePolicy implements ValidationPolicyInterface {
    public function __construct(private WordPressApiWrapperInterface $wp) {}

    /**
     * @param EmailAddress $value
     */
    public function check($value): void {
        if (!$value instanceof EmailAddress) {
            throw new \InvalidArgumentException('This policy requires an EmailAddress object.');
        }

        if ($this->wp->emailExists((string) $value)) {
            throw new Exception('An account with that email already exists.', 409);
        }
    }
}