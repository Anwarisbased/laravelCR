<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ProfileData extends Data
{
    public function __construct(
        #[MapName('first_name')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $firstName = null,
        #[MapName('last_name')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $lastName = null,
        #[MapName('phone_number')]
        #[Validation(['nullable', 'string', 'max:20'])]
        public ?string $phoneNumber = null,
        #[MapName('referral_code')]
        #[Validation(['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/'])]
        public ?string $referralCode = null,
        #[MapName('shipping_address')]
        public ?array $shippingAddress = null,
        #[MapName('unlocked_achievement_keys')]
        #[Validation(['array'])]
        public array $unlockedAchievementKeys = [],
        #[Validation(['array'])]
        public ?array $customFields = null,
    ) {
    }

    /**
     * Create from Profile DTO
     */
    public static function fromProfileDto($profileDto): self
    {
        try {
            return new self(
                firstName: $profileDto->firstName,
                lastName: $profileDto->lastName,
                phoneNumber: $profileDto->phoneNumber ? (string)$profileDto->phoneNumber : null,
                referralCode: $profileDto->referralCode ? (string)$profileDto->referralCode : null,
                shippingAddress: (array) $profileDto->shippingAddress,
                unlockedAchievementKeys: $profileDto->unlockedAchievementKeys,
                customFields: (array) $profileDto->customFields,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'ProfileDTO',
                self::class,
                $e->getMessage()
            );
        }
    }

    /**
     * Create from User model
     */
    public static function fromUserModel(\App\Models\User $user): self
    {
        try {
            return new self(
                firstName: $user->first_name,
                lastName: $user->last_name,
                phoneNumber: $user->phone_number,
                referralCode: $user->referral_code,
                shippingAddress: $user->shipping_address,
                unlockedAchievementKeys: $user->unlocked_achievement_keys ?: [],
                customFields: $user->meta ?: [],
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\User::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}