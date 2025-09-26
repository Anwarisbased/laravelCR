<?php
namespace App\Repositories;

use App\Domain\MetaKeys;
use App\Domain\ValueObjects\UserId;
use App\DTO\ShippingAddressDTO;
use App\Infrastructure\WordPressApiWrapperInterface;

/**
 * User Repository
 *
 * Handles all data access logic for users. This is the single source of truth
 * for fetching and persisting user data, abstracting away the underlying
 * WordPress user and usermeta table implementation.
 */
class UserRepository {
    private WordPressApiWrapperInterface $wp;
    private array $metaCache = []; // Request-level cache for user meta

    public function __construct(WordPressApiWrapperInterface $wp) {
        $this->wp = $wp;
    }
    
    /**
     * Loads all user meta into a request-level cache to prevent N+1 queries.
     */
    private function loadMetaCache(UserId $userId): void {
        $id = $userId->toInt();
        if (!isset($this->metaCache[$id])) {
            $allMeta = $this->wp->getAllUserMeta($id);
            // In Eloquent wrapper, meta values are stored directly, unlike WordPress which stores arrays.
            // So for Eloquent, we use the values as-is. For compatibility with WordPress wrapper,
            // we check if values are arrays (WordPress style) or direct values (Eloquent style).
            $this->metaCache[$id] = array_map(function($meta) {
                if (is_array($meta)) {
                    // WordPress style: array of values, take first one
                    return $meta[0] ?? null;
                } else {
                    // Eloquent style: direct value
                    return $meta;
                }
            }, $allMeta);
        }
    }

    /**
     * Retrieves the core user object (\WP_User).
     * This is one of the few places where returning a WordPress-specific object is acceptable,
     * as it's the raw data source that services will adapt into DTOs.
     */
    public function getUserCoreData(UserId $userId): object|null {
        return $this->wp->getUserById($userId->toInt());
    }
    
    public function getUserCoreDataBy(string $field, string $value): object|null {
        return $this->wp->findUserBy($field, $value);
    }
    
    /**
     * Creates a new WordPress user.
     * @throws \Exception If user creation fails.
     * @return int The new user's ID.
     */
    public function createUser(\App\Domain\ValueObjects\EmailAddress $email, \App\Domain\ValueObjects\PlainTextPassword $password, string $firstName, string $lastName): int {
        $user_id = $this->wp->createUser([
            'user_login' => $email->value,
            'user_email' => $email->value,
            'user_pass'  => $password->value,  // Use the actual password value
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'role' => 'subscriber'
        ]);

        if ($this->wp->isWpError($user_id)) {
            throw new \Exception($user_id->get_error_message(), 500);
        }
        return (int) $user_id;
    }

    /**
     * Saves the initial meta fields for a newly registered user.
     */
    public function saveInitialMeta(UserId $userId, string $phone, bool $agreedToMarketing): void {
        $this->wp->updateUserMeta($userId->toInt(), 'phone_number', $phone);
        $this->wp->updateUserMeta($userId->toInt(), 'marketing_consent', $agreedToMarketing);
        $this->wp->updateUserMeta($userId->toInt(), '_age_gate_confirmed_at', $this->wp->currentTime('mysql', 1));
        
        // Clear the cache to ensure updated meta data is retrieved
        unset($this->metaCache[$userId->toInt()]);
    }

    /**
     * A generic proxy to the wrapper for fetching user meta.
     * Services should use this instead of accessing the wrapper directly for user data.
     */
    public function getUserMeta(UserId $userId, string $key, bool $single = true) {
        return $this->wp->getUserMeta($userId->toInt(), $key, $single);
    }

    public function getPointsBalance(UserId $userId): int {
        $this->loadMetaCache($userId);
        $balance = $this->metaCache[$userId->toInt()][MetaKeys::POINTS_BALANCE] ?? 0;
        return (int) $balance;
    }

    public function getLifetimePoints(UserId $userId): int {
        $this->loadMetaCache($userId);
        $points = $this->metaCache[$userId->toInt()][MetaKeys::LIFETIME_POINTS] ?? 0;
        return (int) $points;
    }

    public function getCurrentRankKey(UserId $userId): string {
        $this->loadMetaCache($userId);
        $rank_key = $this->metaCache[$userId->toInt()][MetaKeys::CURRENT_RANK_KEY] ?? 'member';
        return (string) $rank_key;
    }
    
    public function getReferralCode(UserId $userId): ?string {
        $this->loadMetaCache($userId);
        // Fix: Use the correct meta key constant
        $code = $this->metaCache[$userId->toInt()][MetaKeys::REFERRAL_CODE] ?? null;
        return $code ? (string) $code : null;
    }

    public function findUserIdByReferralCode(string $referral_code): ?int {
        $users = $this->wp->findUsers([
            'meta_key'   => MetaKeys::REFERRAL_CODE,
            'meta_value' => $this->wp->sanitizeTextField($referral_code),
            'number'     => 1,
            'fields'     => 'ID',
        ]);
        return !empty($users) ? (int) $users[0] : null;
    }

    public function getReferringUserId(UserId $userId): ?int {
        $this->loadMetaCache($userId);
        $referrer_id = $this->metaCache[$userId->toInt()][MetaKeys::REFERRED_BY_USER_ID] ?? null;
        return $referrer_id ? (int) $referrer_id : null;
    }

    /**
     * Gets the user's shipping address as a formatted DTO.
     */
    public function getShippingAddressDTO(UserId $userId): ShippingAddressDTO {
        $this->loadMetaCache($userId);
        $id = $userId->toInt();
        $cache = $this->metaCache[$id];
        return new ShippingAddressDTO(
            firstName: $cache['shipping_first_name'] ?? '',
            lastName: $cache['shipping_last_name'] ?? '',
            address1: $cache['shipping_address_1'] ?? '',
            city: $cache['shipping_city'] ?? '',
            state: $cache['shipping_state'] ?? '',
            postcode: $cache['shipping_postcode'] ?? ''
        );
    }

    /**
     * Gets the user's shipping address as a simple associative array.
     */
    public function getShippingAddressArray(UserId $userId): array {
        return (array) $this->getShippingAddressDTO($userId);
    }

    public function savePointsAndRank(UserId $userId, int $new_balance, int $new_lifetime_points, string $new_rank_key): void {
        $userIdInt = $userId->toInt();
        
        // Update the database
        $this->wp->updateUserMeta($userIdInt, MetaKeys::POINTS_BALANCE, $new_balance);
        $this->wp->updateUserMeta($userIdInt, MetaKeys::LIFETIME_POINTS, $new_lifetime_points);
        $this->wp->updateUserMeta($userIdInt, MetaKeys::CURRENT_RANK_KEY, $new_rank_key);
        
        // Clear the cache to ensure the updated values are retrieved from database
        unset($this->metaCache[$userIdInt]);
    }
    
    public function saveReferralCode(UserId $userId, string $code): void {
        $this->wp->updateUserMeta($userId->toInt(), MetaKeys::REFERRAL_CODE, $code);
    }
    
    public function setReferredBy(UserId $newUserId, UserId $referrerUserId): void {
        $this->wp->updateUserMeta($newUserId->toInt(), MetaKeys::REFERRED_BY_USER_ID, $referrerUserId->toInt());
    }
    
    public function saveShippingAddress(UserId $userId, array $shipping_details): void {
        if (empty($shipping_details) || !isset($shipping_details['firstName'])) {
            return;
        }

        $meta_map = [
            'firstName' => 'shipping_first_name',
            'lastName'  => 'shipping_last_name',
            'address1'  => 'shipping_address_1',
            'city'      => 'shipping_city',
            'state'     => 'shipping_state',
            'zip'       => 'shipping_postcode',
        ];

        foreach ($meta_map as $frontend_key => $meta_key) {
            if (isset($shipping_details[$frontend_key])) {
                $this->wp->updateUserMeta($userId->toInt(), $meta_key, $this->wp->sanitizeTextField($shipping_details[$frontend_key]));
            }
        }
        
        $this->wp->updateUserMeta( $userId->toInt(), 'billing_first_name', $this->wp->sanitizeTextField( $shipping_details['firstName'] ?? '' ) );
        $this->wp->updateUserMeta( $userId->toInt(), 'billing_last_name', $this->wp->sanitizeTextField( $shipping_details['lastName'] ?? '' ) );
        
        // Clear the cache to ensure updated shipping data is retrieved
        unset($this->metaCache[$userId->toInt()]);
    }
    
    /**
     * Updates a user's core data (first name, last name, etc.).
     * @param UserId $userId The user ID
     * @param array $data Associative array of user data to update
     * @return int|\WP_Error The updated user's ID on success, or a WP_Error object on failure.
     */
    public function updateUserData(UserId $userId, array $data) {
        $data['ID'] = $userId->toInt();
        return $this->wp->updateUser($data);
    }
    
    /**
     * Updates a user meta field.
     * @param UserId $userId The user ID
     * @param string $meta_key The meta key to update
     * @param mixed $meta_value The meta value to set
     * @param mixed $prev_value Optional. Previous value to check before updating.
     * @return bool True on success, false on failure.
     */
    public function updateUserMetaField(UserId $userId, string $meta_key, $meta_value, $prev_value = '') {
        $result = $this->wp->updateUserMeta($userId->toInt(), $meta_key, $meta_value, $prev_value);
        // Clear the cache so that the next fetch gets fresh data
        unset($this->metaCache[$userId->toInt()]);
        return $result;
    }

    /**
     * FOR TESTING PURPOSES ONLY.
     * Clears the internal request-level cache for a specific user.
     * This is necessary in tests where we manually change user meta
     * after the user object might have already been loaded in the same request cycle.
     */
    public function clearCacheForUser(UserId $userId): void
    {
        unset($this->metaCache[$userId->toInt()]);
    }
}