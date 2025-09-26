<?php

namespace App\Infrastructure;

use App\Models\Product;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception as WP_Error; // Use the same alias as the interface

class EloquentApiWrapper implements WordPressApiWrapperInterface
{
    public $db; // WordPress database object compatibility
    
    public function __construct()
    {
        // Initialize the db object to mimic WordPress' $wpdb
        $this->db = (object) [
            'prefix' => '' // Laravel doesn't typically use table prefixes
        ];
    }

    // User & Meta Functions
    public function getUserMeta(int $userId, string $key, bool $single = true)
    {
        $user = User::find($userId);
        return $user->meta[$key] ?? null;
    }

    public function updateUserMeta(int $userId, string $key, $value): void
    {
        $user = User::find($userId);
        if ($user) {
            $meta = $user->meta ?? []; // Get existing meta or start a new array
            $meta[$key] = $value;
            $user->meta = $meta; // Put the updated array back
            $user->save();
        }
    }

    public function getUserById(int $userId): ?object
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        
        // Create a stdClass object that mimics WP_User structure
        $wpUser = new \stdClass();
        $wpUser->ID = $user->id;
        $wpUser->user_login = $user->email; // Using email as login
        $wpUser->user_email = $user->email;
        $wpUser->user_nicename = $user->name; // This might need adjustment
        $wpUser->user_registered = $user->created_at;
        $wpUser->user_status = 0;
        $wpUser->display_name = $user->name;
        
        // Split name into first and last for compatibility
        $nameParts = explode(' ', $user->name, 2);
        $wpUser->first_name = $nameParts[0] ?? '';
        $wpUser->last_name = $nameParts[1] ?? '';
        
        return $wpUser;
    }

    public function findUserBy(string $field, string $value): ?object
    {
        // Map common WordPress field names to Laravel column names
        $fieldMap = [
            'user_email' => 'email',
            'user_login' => 'email',
            'ID' => 'id'
        ];
        
        $mappedField = $fieldMap[$field] ?? $field;
        
        $user = User::where($mappedField, $value)->first();
        if (!$user) {
            return null;
        }
        
        // Create a stdClass object that mimics WP_User structure
        $wpUser = new \stdClass();
        $wpUser->ID = $user->id;
        $wpUser->user_login = $user->email; // Using email as login
        $wpUser->user_email = $user->email;
        $wpUser->user_nicename = $user->name; // This might need adjustment
        $wpUser->user_registered = $user->created_at;
        $wpUser->user_status = 0;
        $wpUser->display_name = $user->name;
        
        // Split name into first and last for compatibility
        $nameParts = explode(' ', $user->name, 2);
        $wpUser->first_name = $nameParts[0] ?? '';
        $wpUser->last_name = $nameParts[1] ?? '';
        
        return $wpUser;
    }

    public function createUser(array $userData): int|WP_Error
    {
        $user = User::create([
            'name' => $userData['first_name'] . ' ' . ($userData['last_name'] ?? ''),
            'email' => $userData['user_email'],
            'password' => Hash::make($userData['user_pass']),
        ]);
        return $user->id;
    }
    
    public function updateUser(array $userData): int|WP_Error
    {
        $user = User::find($userData['ID']);
        $user->update($userData);
        return $user->id;
    }

    public function getAllUserMeta(int $userId): array
    {
        $user = User::find($userId);
        return $user->meta ?? [];
    }

    // Post & Query Functions
    public function getPosts(array $args): array
    {
        // In a real implementation, this would query posts from the database
        // For now, we'll just return an empty array
        return [];
    }
    
    public function getPostMeta(int $postId, string $key, bool $single = true)
    {
        // Get the product model
        $product = Product::find($postId);
        
        if (!$product) {
            return $single ? '' : [];
        }
        
        // Get the meta value from the product's meta property
        $metaValue = $product->meta[$key] ?? null;
        
        if ($single) {
            // Return the first value if it exists, otherwise return empty string
            return $metaValue ?? '';
        } else {
            // Return an array of values
            return $metaValue !== null ? [$metaValue] : [];
        }
    }
    
    public function updatePostMeta(int $postId, string $key, $value): bool
    {
        // Get the product model
        $product = Product::find($postId);
        
        if (!$product) {
            return false;
        }
        
        // Update the meta value
        $product->meta = array_merge($product->meta ?? [], [$key => $value]);
        return $product->save();
    }

    // Options & Transients (Cache)
    public function getOption(string $option, $default = false)
    {
        // Laravel doesn't have options like WordPress, so we'll store these in cache or config
        $value = Cache::get('wp_option_' . $option);
        return $value !== null ? $value : $default;
    }

    public function getTransient(string $key)
    {
        return Cache::get($key);
    }

    public function setTransient(string $key, $value, int $expiration): void
    {
        Cache::put($key, $value, $expiration);
    }

    public function deleteTransient(string $key): bool
    {
        return Cache::forget($key);
    }

    // WooCommerce Functions
    public function getProductIdBySku(string $sku): int
    {
        return Product::where('sku', $sku)->value('id') ?? 0;
    }

    public function getProduct(int $productId): ?object
    {
        return Product::find($productId);
    }

    // WordPress Core Functions
    public function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function generatePassword(int $length, bool $special_chars, bool $extra_special_chars): string
    {
        // Laravel's helper is good enough for this purpose
        return Str::random($length);
    }
    
    // Helper functions to replace WordPress functions
    public function isWpError($thing): bool
    {
        return $thing instanceof \WP_Error;
    }
    
    public function currentTime($type = 'mysql', $gmt = 0)
    {
        if ($type === 'timestamp' || $type === 'U') {
            return time();
        }
        
        if ($gmt) {
            return gmdate('Y-m-d H:i:s');
        }
        
        return date('Y-m-d H:i:s');
    }
    
    public function wpJsonEncode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options, $depth);
    }
    
    public function sanitizeTextField($str)
    {
        // Laravel's request validation handles this, but we'll provide a basic implementation
        return trim(strip_tags((string) $str));
    }
    
    public function sanitizeKey($key)
    {
        // Sanitizes a string key
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
    
    public function getCurrentUserId()
    {
        // This should be handled by Laravel's auth system
        // For now, we'll return 0 as a fallback
        return auth()->id() ?? 0;
    }
    
    public function getTheTitle(int $postId): string
    {
        $product = Product::find($postId);
        return $product ? $product->name : 'Unknown Product';
    }
    
    public function getPost(int $postId): ?object
    {
        $product = Product::find($postId);
        if (!$product) {
            return null;
        }
        
        // Create a stdClass object that mimics a WordPress post structure
        $wpPost = new \stdClass();
        $wpPost->ID = $product->id;
        $wpPost->post_title = $product->name;
        $wpPost->post_content = $product->description ?? '';
        $wpPost->post_excerpt = '';
        $wpPost->post_status = 'publish';
        $wpPost->post_type = 'product';
        $wpPost->post_date = $product->created_at;
        $wpPost->post_date_gmt = gmdate('Y-m-d H:i:s', strtotime($product->created_at));
        
        return $wpPost;
    }
    
    public function dbInsert(string $table, array $data, ?array $format = null): int|bool
    {
        try {
            // Use the table name directly since we created it in Laravel migration
            // The table name should be 'canna_user_action_log' as expected by the code
            $id = DB::table($table)->insertGetId($data);
            return $id ?: false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("dbInsert failed: " . $e->getMessage());
            return false;
        }
    }

    public function dbUpdate(string $table, array $data, array $where, ?array $format = null, ?array $where_format = null): int|bool
    {
        try {
            $query = DB::table($table);
            
            // Apply where conditions
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }
            
            // Perform the update
            $result = $query->update($data);
            
            return $result; // Number of affected rows
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("dbUpdate failed: " . $e->getMessage());
            return false;
        }
    }

    public function dbGetCol(string $query): array
    {
        // Execute the query and return the first column of all rows
        $results = DB::select($query);
        
        // Extract the first column from each row
        $column = [];
        foreach ($results as $row) {
            $arr = (array)$row;
            $column[] = reset($arr); // Get the first value
        }
        
        return $column;
    }

    public function dbPrepare(string $query, ...$args): string
    {
        // For string queries, replace the placeholders directly with the values, properly quoted for safety
        $pdo = DB::getPdo();
        
        $result = $query;
        foreach ($args as $arg) {
            if (strpos($result, '%d') !== false) {
                // Replace integer placeholder with actual integer
                $result = preg_replace('/%d/', (int)$arg, $result, 1);
            } elseif (strpos($result, '%s') !== false) {
                // Replace string placeholder with properly quoted string
                $result = preg_replace('/%s/', $pdo->quote((string)$arg), $result, 1);
            }
        }
        
        return $result;
    }

    public function dbGetResults(string $query): array
    {
        // Execute the query and return results
        $results = DB::select($query);
        
        // Convert results to array of objects like WordPress would
        return $results;
    }

    public function dbGetVar(string $query)
    {
        // Execute the query and return the first column of the first row
        $result = DB::selectOne($query);
        
        if ($result) {
            // Return the first property of the result object (since it's a count query)
            $arr = (array)$result;
            return reset($arr);
        }
        return null;
    }

    public function dbGetRow(string $query, string $output = \OBJECT)
    {
        // Execute the query and return the first row
        $result = DB::selectOne($query);
        
        // If result is null, return null
        if ($result === null) {
            return null;
        }
        
        // Return result based on the output format
        switch ($output) {
            case \OBJECT:
            default:
                return $result;
            case \ARRAY_A:
                return (array)$result;
            case \ARRAY_N:
                return array_values((array)$result);
        }
    }

    public function getDbPrefix(): string
    {
        // Return the default Laravel table prefix (usually empty)
        return '';
    }
    
    public function findUsers(array $args): array
    {
        // Build query based on the args passed in
        $query = User::query();

        // Handle meta_key and meta_value
        if (isset($args['meta_key']) && isset($args['meta_value'])) {
            // For Laravel's JSON column, use -> to access nested properties
            $query->where('meta->' . $args['meta_key'], $args['meta_value']);
        }

        // Handle number (limit)
        if (isset($args['number'])) {
            $query->limit($args['number']);
        }

        // Handle fields
        $fields = $args['fields'] ?? 'all'; // Default to 'all' like WordPress

        if ($fields === 'ID') {
            // Return only the IDs
            return $query->pluck('id')->toArray();
        } else {
            // Return full user objects
            return $query->get()->toArray();
        }
    }

    public function createOrder(array $args = [])
    {
        try {
            // Extract customer_id and product_id from args
            $customerId = $args['customer_id'] ?? null;
            $productId = $args['product_id'] ?? null;
            
            \Illuminate\Support\Facades\Log::info('Creating order with customer ID: ' . ($customerId ?? 'null') . ' and product ID: ' . ($productId ?? 'null'));
            
            if (!$customerId) {
                throw new \Exception('Customer ID is required to create an order.');
            }
            
            // Create a new order instance
            $order = new \App\Models\Order();
            $order->user_id = $customerId;
            $order->product_id = $productId; // This can be null initially
            $order->status = 'pending';
            $order->total = 0;
            $order->is_redemption = true;
            $order->save();
            
            \Illuminate\Support\Facades\Log::info('Order created successfully with ID: ' . $order->id);
            
            return $order;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order creation failed: ' . $e->getMessage());
            // Return a WP_Error-like object for compatibility
            return new WP_Error('order_creation_failed', $e->getMessage());
        }
    }
    
    // Password Reset Functions
    public function getPasswordResetKey($user): string
    {
        // Create a password reset token for the user
        // In WordPress, this would use wp_generate_password_reset_key
        $token = Str::random(20); // Generate a unique token
        
        // Store the token temporarily (in cache or session) with associated user info
        // WordPress uses a special table, but we'll use Laravel's cache
        $cache_key = 'password_reset_' . $user->ID . '_' . $token;
        Cache::put($cache_key, $user->user_email, 60 * 15); // 15 minutes expiry
        
        return $token;
    }

    public function checkPasswordResetKey(string $key, string $email)
    {
        // Check if the password reset key is valid
        // Find the cached data based on the key and email
        
        // WordPress stores reset keys in the usermeta table with a timestamp
        // For our Laravel implementation, we'll look up in cache
        
        // In a real implementation, we'd find all user reset tokens and match the key
        // For this simplified version, we'll find a matching cache entry
        
        // Check if a matching cache key exists
        $users = User::all();
        foreach ($users as $user) {
            $cache_key = 'password_reset_' . $user->id . '_' . $key;
            $cached_email = Cache::get($cache_key);
            
            if ($cached_email === $email) {
                // Valid key, return user object
                Cache::forget($cache_key); // Remove the key after use
                
                // Return user object that matches Laravel's User model
                return $this->getUserById($user->id);
            }
        }
        
        // Key not found or doesn't match the email
        return new WP_Error('invalid_key', 'Invalid password reset key.');
    }

    public function resetPassword(object $user, string $newPassword): void
    {
        // Reset the user's password
        $laravel_user = User::find($user->ID);
        
        if ($laravel_user) {
            $laravel_user->password = Hash::make($newPassword);
            $laravel_user->save();
        }
    }

    public function sendMail(string $to, string $subject, string $message, array $headers = []): bool
    {
        // In a real implementation, you would send an email
        // For now, we'll just log it for testing purposes
        \Illuminate\Support\Facades\Log::info("Email sent to: $to, Subject: $subject, Message: $message");
        
        // Return true to indicate success
        return true;
    }
    
    public function homeUrl(): string
    {
        // Return the site URL (for testing, we'll return the app URL)
        return config('app.url', 'http://localhost');
    }
}
