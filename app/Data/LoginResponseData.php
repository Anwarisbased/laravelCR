<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class LoginResponseData extends Data
{
    public function __construct(
        public string $token,
        public string $user_email,
        public string $user_nicename,
        public string $user_display_name,
    ) {
    }
    
    public static function fromServiceResponse(string $token, string $user_email, string $user_nicename, string $user_display_name): self
    {
        try {
            return new self(
                token: $token,
                user_email: $user_email,
                user_nicename: $user_nicename,
                user_display_name: $user_display_name
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Service Response',
                self::class,
                $e->getMessage()
            );
        }
    }
}