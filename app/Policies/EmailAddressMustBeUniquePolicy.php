<?php
namespace App\Policies;

use App\Domain\ValueObjects\EmailAddress;
use App\Models\User;
use Exception;

class EmailAddressMustBeUniquePolicy implements ValidationPolicyInterface {
    /**
     * @param EmailAddress $value
     */
    public function check($value): void {
        if (!$value instanceof EmailAddress) {
            throw new \InvalidArgumentException('This policy requires an EmailAddress object.');
        }

        $user = User::where('email', (string) $value)->first();
        if ($user) {
            throw new Exception('An account with that email already exists.');
        }
    }
}