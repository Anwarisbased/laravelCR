<?php
namespace App\Repositories;

use App\Domain\MetaKeys;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Sku;
use App\Infrastructure\WordPressApiWrapperInterface;

// Exit if accessed directly.

/**
 * Product Repository
 * Handles data access for WooCommerce products.
 */
class ProductRepository {
    private WordPressApiWrapperInterface $wp;

    public function __construct(WordPressApiWrapperInterface $wp) {
        $this->wp = $wp;
    }

    public function findIdBySku(Sku $sku): ?ProductId {
        $product_id = $this->wp->getProductIdBySku($sku->value);
        return $product_id > 0 ? ProductId::fromInt($product_id) : null;
    }

    public function getPointsAward(ProductId $product_id): int {
        $product = $this->wp->getProduct($product_id->toInt());
        if (!$product) {
            return 0;
        }
        return (int) ($product->points_award ?? 0);
    }

    public function getPointsCost(ProductId $product_id): int {
        $product = $this->wp->getProduct($product_id->toInt());
        if (!$product) {
            return 0;
        }
        return (int) ($product->points_cost ?? 0);
    }
    
    public function getRequiredRank(ProductId $product_id): ?string {
        $product = $this->wp->getProduct($product_id->toInt());
        if (!$product) {
            return null;
        }
        $rank = $product->required_rank ?? '';
        return empty($rank) ? null : $rank;
    }
}