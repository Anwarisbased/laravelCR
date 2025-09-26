<?php
namespace App\DTO;

use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\ReferralCode;

final class FullProfileDTO {
    /**
     * @param string[] $unlockedAchievementKeys
     */
    public function __construct(
        public readonly string $firstName,
        public readonly ?string $lastName,
        public readonly ?PhoneNumber $phoneNumber,
        public readonly ?ReferralCode $referralCode,
        public readonly ?ShippingAddressDTO $shippingAddress,
        public readonly array $unlockedAchievementKeys = [],
        public readonly object $customFields
    ) {}
}