<?php
namespace App\Repositories;

use App\Domain\MetaKeys;
use App\Domain\ValueObjects\UserId;
use App\DTO\ShippingAddressDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * User Repository
 *
 * Handles all data access logic for users. This is the single source of truth
 * for fetching and persisting user data using Eloquent models.
 */
class UserRepository 
{
    /**
     * Retrieves the core user object (User model).
     * This returns the Laravel User model.
     */
    public function getUserCoreData(UserId $userId): ?User 
    {
        return User::find($userId->toInt());
    }
    
    public function getUserCoreDataBy(string $field, string $value): ?User 
    {
        return User::where($field, $value)->first();
    }
    
    /**
     * Creates a new user.
     * @throws \Exception If user creation fails.
     * @return int The new user's ID.
     */
    public function createUser(\App\Domain\ValueObjects\EmailAddress $email, \App\Domain\ValueObjects\PlainTextPassword $password, string $firstName, string $lastName): int 
    {
        $user = User::create([
            'name' => $firstName . ' ' . $lastName,
            'email' => $email->value,
            'password' => $password->value,
            'meta' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]
        ]);

        if (!$user) {
            throw new \Exception('Failed to create user', 500);
        }

        return $user->id;
    }

    /**
     * Saves the initial meta fields for a newly registered user.
     */
    public function saveInitialMeta(UserId $userId, string $phone, bool $agreedToMarketing): void 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $user->update([
            'meta' => array_merge($user->meta ?: [], [
                'phone_number' => $phone,
                'marketing_consent' => $agreedToMarketing,
                '_age_gate_confirmed_at' => now()->format('Y-m-d H:i:s'),
            ])
        ]);
    }

    /**
     * Gets a user meta field.
     */
    public function getUserMeta(UserId $userId, string $key, bool $single = true) 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return null;
        }

        $meta = $user->meta ?: [];
        return $meta[$key] ?? null;
    }

    public function getPointsBalance(UserId $userId): int 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return 0;
        }

        $meta = $user->meta ?: [];
        return (int) ($meta[MetaKeys::POINTS_BALANCE] ?? 0);
    }

    public function getLifetimePoints(UserId $userId): int 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return 0;
        }

        $meta = $user->meta ?: [];
        return (int) ($meta[MetaKeys::LIFETIME_POINTS] ?? 0);
    }

    public function getCurrentRankKey(UserId $userId): string 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return 'member';
        }

        $meta = $user->meta ?: [];
        return (string) ($meta[MetaKeys::CURRENT_RANK_KEY] ?? 'member');
    }
    
    public function getReferralCode(UserId $userId): ?string 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return null;
        }

        $meta = $user->meta ?: [];
        $code = $meta[MetaKeys::REFERRAL_CODE] ?? null;
        return $code ? (string) $code : null;
    }

    public function findUserIdByReferralCode(string $referral_code): ?int 
    {
        $user = User::whereJsonContains('meta', [MetaKeys::REFERRAL_CODE => $referral_code])
                    ->orWhereRaw("JSON_EXTRACT(meta, '$." . MetaKeys::REFERRAL_CODE . "') = ?", [$referral_code])
                    ->first();

        return $user ? $user->id : null;
    }

    public function getReferringUserId(UserId $userId): ?int 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return null;
        }

        $meta = $user->meta ?: [];
        $referrer_id = $meta[MetaKeys::REFERRED_BY_USER_ID] ?? null;
        return $referrer_id ? (int) $referrer_id : null;
    }

    /**
     * Gets the user's shipping address as a formatted DTO.
     */
    public function getShippingAddressDTO(UserId $userId): ShippingAddressDTO 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return new ShippingAddressDTO(
                firstName: '',
                lastName: '',
                address1: '',
                city: '',
                state: '',
                postcode: ''
            );
        }

        $meta = $user->meta ?: [];
        return new ShippingAddressDTO(
            firstName: $meta['shipping_first_name'] ?? '',
            lastName: $meta['shipping_last_name'] ?? '',
            address1: $meta['shipping_address_1'] ?? '',
            city: $meta['shipping_city'] ?? '',
            state: $meta['shipping_state'] ?? '',
            postcode: $meta['shipping_postcode'] ?? ''
        );
    }

    /**
     * Gets the user's shipping address as a simple associative array.
     */
    public function getShippingAddressArray(UserId $userId): array 
    {
        return (array) $this->getShippingAddressDTO($userId);
    }

    public function savePointsAndRank(UserId $userId, int $new_balance, int $new_lifetime_points, string $new_rank_key): void 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $user->update([
            'meta' => array_merge($user->meta ?: [], [
                MetaKeys::POINTS_BALANCE => $new_balance,
                MetaKeys::LIFETIME_POINTS => $new_lifetime_points,
                MetaKeys::CURRENT_RANK_KEY => $new_rank_key,
            ])
        ]);
    }
    
    public function saveReferralCode(UserId $userId, string $code): void 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $user->update([
            'meta' => array_merge($user->meta ?: [], [
                MetaKeys::REFERRAL_CODE => $code,
            ])
        ]);
    }
    
    public function setReferredBy(UserId $newUserId, UserId $referrerUserId): void 
    {
        $user = User::find($newUserId->toInt());
        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $user->update([
            'meta' => array_merge($user->meta ?: [], [
                MetaKeys::REFERRED_BY_USER_ID => $referrerUserId->toInt(),
            ])
        ]);
    }
    
    public function saveShippingAddress(UserId $userId, array $shipping_details): void 
    {
        if (empty($shipping_details) || !isset($shipping_details['firstName'])) {
            return;
        }

        $user = User::find($userId->toInt());
        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $meta = $user->meta ?: [];

        $meta['shipping_first_name'] = $shipping_details['firstName'] ?? '';
        $meta['shipping_last_name'] = $shipping_details['lastName'] ?? '';
        $meta['shipping_address_1'] = $shipping_details['address1'] ?? '';
        $meta['shipping_city'] = $shipping_details['city'] ?? '';
        $meta['shipping_state'] = $shipping_details['state'] ?? '';
        $meta['shipping_postcode'] = $shipping_details['zip'] ?? '';

        // Also set billing details
        $meta['billing_first_name'] = $shipping_details['firstName'] ?? '';
        $meta['billing_last_name'] = $shipping_details['lastName'] ?? '';

        $user->update([
            'meta' => $meta
        ]);
    }
    
    /**
     * Updates a user's core data (first name, last name, etc.).
     * @param UserId $userId The user ID
     * @param array $data Associative array of user data to update
     * @return bool True on success, false on failure.
     */
    public function updateUserData(UserId $userId, array $data) 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return false;
        }

        $updated = $user->update($data);
        return $updated;
    }
    
    /**
     * Updates a user meta field.
     * @param UserId $userId The user ID
     * @param string $meta_key The meta key to update
     * @param mixed $meta_value The meta value to set
     * @return bool True on success, false on failure.
     */
    public function updateUserMetaField(UserId $userId, string $meta_key, $meta_value): bool 
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return false;
        }

        $meta = $user->meta ?: [];
        $meta[$meta_key] = $meta_value;

        return $user->update([
            'meta' => $meta
        ]);
    }

    /**
     * FOR TESTING PURPOSES ONLY.
     * This method is not needed in Eloquent implementation since we don't use request-level caching.
     */
    public function clearCacheForUser(UserId $userId): void
    {
        // No cache to clear in Eloquent implementation
    }
}