<?php
namespace App\Services;

use App\Repositories\ActionLogRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CatalogService {
    private ConfigService $configService;
    private ActionLogRepository $logRepo;

    public function __construct(ConfigService $configService, ActionLogRepository $logRepo) {
        $this->configService = $configService;
        $this->logRepo = $logRepo;
    }
    
    public function get_all_reward_products(): array {
        // In a pure Laravel implementation, we'd query from a products table
        $products = DB::table('products')
            ->where('status', 'publish')
            ->whereNotNull('points_cost')
            ->get();

        $formatted_products = [];
        foreach ($products as $product) {
            // Only include products that can be redeemed (i.e., have a points_cost).
            $points_cost = $product->points_cost;
            if (!empty($points_cost)) {
                $formatted_products[] = $this->format_product_for_api($product);
            }
        }

        return $formatted_products;
    }

    public function get_product_with_eligibility(int $product_id, int $user_id): ?array {
        // In a pure Laravel implementation, we'd query from a products table
        $product = DB::table('products')->where('id', $product_id)->first();
        if (!$product) {
            return null;
        }

        $formatted_product = $this->format_product_for_api($product);
        $formatted_product['is_eligible_for_free_claim'] = $this->is_user_eligible_for_free_claim($product_id, $user_id);

        return $formatted_product;
    }
    
    private function is_user_eligible_for_free_claim(int $product_id, int $user_id): bool {
        if ($user_id <= 0) {
            return false;
        }
        
        $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
        $referral_gift_id = $this->configService->getReferralSignupGiftId();

        if ($product_id === $welcome_reward_id || $product_id === $referral_gift_id) {
            $scan_count = $this->logRepo->countUserActions($user_id, 'scan');
            return $scan_count <= 1;
        }
        
        return false;
    }

    /**
     * A helper function to consistently format product data for the API response.
     * This ensures the frontend receives data in the exact structure it expects.
     *
     * @param object $product The product object.
     * @return array The formatted product data.
     */
    public function format_product_for_api($product): array {
        // In a pure Laravel implementation, we'd have proper image handling
        $image_url = Storage::url('products/' . $product->id . '.jpg');
        if (!Storage::exists('products/' . $product->id . '.jpg')) {
            $image_url = '/images/placeholder.png'; // Using Laravel placeholder
        }

        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'description' => $product->description ?? '',
            'images'      => [
                ['src' => $image_url]
            ],
            'meta_data'   => [
                [
                    'key'   => 'points_cost',
                    'value' => $product->points_cost ?? 0,
                ],
                [
                    'key'   => '_required_rank',
                    'value' => $product->required_rank ?? '',
                ],
            ],
        ];
    }
}