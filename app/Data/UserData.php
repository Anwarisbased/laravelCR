<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public int $id,
        #[Validation(['required', 'string', 'max:255'])]
        public string $name,
        #[Validation(['required', 'email', 'max:255'])]
        public string $email,
        #[MapName('username')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $username = null,
        #[MapName('referral_code')]
        #[Validation(['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/'])]
        public ?string $referralCode = null,
        #[MapName('points_balance')]
        #[Validation(['integer', 'min:0'])]
        public int $pointsBalance = 0,
        #[MapName('lifetime_points')]
        #[Validation(['integer', 'min:0'])]
        public int $lifetimePoints = 0,
        #[MapName('current_rank_key')]
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $currentRankKey = null,
        #[MapName('device_token')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $deviceToken = null,
        #[MapName('phone_number')]
        #[Validation(['nullable', 'string', 'max:20'])]
        public ?string $phoneNumber = null,
        #[MapName('date_of_birth')]
        #[Validation(['nullable', 'date'])]
        public ?string $dateOfBirth = null,
        #[MapName('gender')]
        #[Validation(['nullable', 'string', 'max:20'])]
        public ?string $gender = null,
        #[MapName('address')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $address = null,
        #[MapName('city')]
        #[Validation(['nullable', 'string', 'max:100'])]
        public ?string $city = null,
        #[MapName('state')]
        #[Validation(['nullable', 'string', 'max:100'])]
        public ?string $state = null,
        #[MapName('zip_code')]
        #[Validation(['nullable', 'string', 'max:20'])]
        public ?string $zipCode = null,
        #[MapName('country')]
        #[Validation(['nullable', 'string', 'max:100'])]
        public ?string $country = null,
        #[MapName('is_verified')]
        #[Validation(['boolean'])]
        public bool $isVerified = false,
        #[MapName('is_subscribed_to_notifications')]
        #[Validation(['boolean'])]
        public bool $isSubscribedToNotifications = true,
        #[MapName('last_login_at')]
        #[Validation(['nullable', 'date'])]
        public ?string $lastLoginAt = null,
        #[MapName('created_at')]
        public ?string $createdAt = null,
        #[MapName('updated_at')]
        public ?string $updatedAt = null,
        #[Validation(['array'])]
        public ?array $meta = [],
    ) {
    }

    public static function fromModel(\App\Models\User $user): self
    {
        try {
            return new self(
                id: $user->id,
                name: $user->name,
                email: $user->email,
                username: $user->username ?? null,
                referralCode: $user->referral_code,
                pointsBalance: $user->points_balance ?? 0,
                lifetimePoints: $user->lifetime_points ?? 0,
                currentRankKey: $user->current_rank_key,
                deviceToken: $user->device_token ?? null,
                phoneNumber: $user->phone_number ?? null,
                dateOfBirth: $user->date_of_birth ?? null,
                gender: $user->gender ?? null,
                address: $user->address ?? null,
                city: $user->city ?? null,
                state: $user->state ?? null,
                zipCode: $user->zip_code ?? null,
                country: $user->country ?? null,
                isVerified: $user->email_verified_at !== null,
                isSubscribedToNotifications: $user->is_subscribed_to_notifications ?? true,
                lastLoginAt: $user->last_login_at ?? null,
                createdAt: $user->created_at,
                updatedAt: $user->updated_at,
                meta: $user->meta ?? [],
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