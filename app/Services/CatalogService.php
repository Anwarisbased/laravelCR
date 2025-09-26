<?php
namespace App\Services;

use App\Infrastructure\WordPressApiWrapperInterface;
use App\Repositories\ActionLogRepository;

final class CatalogService {
    private WordPressApiWrapperInterface $wp;
    private ConfigService $configService;
    private ActionLogRepository $logRepo;

    public function __construct(WordPressApiWrapperInterface $wp, ConfigService $configService, ActionLogRepository $logRepo) {
        $this->wp = $wp;
        $this->configService = $configService;
        $this->logRepo = $logRepo;
    }
    
    public function get_all_reward_products(): array {
        // <<<--- REFACTOR: Use the wrapper
        $products = $this->wp->getProducts([
            'status' => 'publish',
            'limit'  => -1,
        ]);

        $formatted_products = [];
        foreach ($products as $product) {
            // Only include products that can be redeemed (i.e., have a points_cost).
            $points_cost = $product->get_meta('points_cost');
            if (!empty($points_cost)) {
                $formatted_products[] = $this->format_product_for_api($product);
            }
        }

        return $formatted_products;
    }

    public function get_product_with_eligibility(int $product_id, int $user_id): ?array {
        // <<<--- REFACTOR: Use the wrapper
        $product = $this->wp->getProduct($product_id);
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
     * @param \WC_Product $product The WooCommerce product object.
     * @return array The formatted product data.
     */
    public function format_product_for_api($product): array {
        $image_id = $product->get_image_id();
        // Use wrapper methods for WordPress functions
        $image_url = $image_id ? $this->wp->getAttachmentImageUrl($image_id, 'woocommerce_thumbnail') : $this->wp->getPlaceholderImageSrc();

        return [
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            'description' => $product->get_description(),
            'images'      => [
                ['src' => $image_url]
            ],
            'meta_data'   => [
                [
                    'key'   => 'points_cost',
                    'value' => $product->get_meta('points_cost'),
                ],
                [
                    'key'   => '_required_rank',
                    'value' => $product->get_meta('_required_rank'),
                ],
            ],
        ];
    }
}