<?php

namespace App\Infrastructure;

// We will replace this with a Laravel Exception later
use Exception as WP_Error;

interface WordPressApiWrapperInterface
{
    // User & Meta Functions
    public function getUserMeta(int $userId, string $key, bool $single = true);
    public function updateUserMeta(int $userId, string $key, $value): void;
    public function getUserById(int $userId): ?object;
    public function findUserBy(string $field, string $value): ?object;
    public function createUser(array $userData): int|WP_Error;
    public function updateUser(array $userData): int|WP_Error;
    public function getAllUserMeta(int $userId): array;

    // Post & Query Functions
    public function getPosts(array $args): array;
    
    // Options & Transients (Cache)
    public function getOption(string $option, $default = false);
    public function getTransient(string $key);
    public function setTransient(string $key, $value, int $expiration): void;
    public function deleteTransient(string $key): bool;

    // WooCommerce Functions
    public function getProductIdBySku(string $sku): int;
    public function getProduct(int $productId): ?object;
    
    // WordPress Core Functions
    public function isEmail(string $email): bool;
    public function emailExists(string $email): bool;
    public function generatePassword(int $length, bool $special_chars, bool $extra_special_chars): string;
    public function findUsers(array $args): array;
    
    // Helper functions to replace WordPress functions
    public function isWpError($thing): bool;
    public function currentTime($type = 'mysql', $gmt = 0);
    public function wpJsonEncode($data, $options = 0, $depth = 512);
    public function sanitizeTextField($str);
    public function sanitizeKey($key);
    public function getCurrentUserId();
    
    // Additional WordPress functions
    public function getTheTitle(int $postId): string;
    public function getPost(int $postId): ?object;
    
    // Database operations
    public function dbInsert(string $table, array $data, ?array $format = null): int|bool;
    public function dbUpdate(string $table, array $data, array $where, ?array $format = null, ?array $where_format = null): int|bool;
    public function dbGetRow(string $query, string $output = \OBJECT);
    public function dbGetCol(string $query): array;
    public function dbPrepare(string $query, ...$args): string;
    public function dbGetResults(string $query): array;
    public function dbGetVar(string $query);
    public function getDbPrefix(): string;
}
