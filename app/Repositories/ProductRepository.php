<?php
namespace App\Repositories;

use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Sku;
use Illuminate\Support\Facades\DB;

// Exit if accessed directly.

/**
 * Product Repository
 * Handles data access for products.
 */
class ProductRepository {

    public function findIdBySku(Sku $sku): ?ProductId {
        // In a pure Laravel implementation, we'd have a products table
        // For now, let's return null to indicate product not found
        // In a real implementation, we would query from the products table
        $product = DB::table('products')->where('sku', $sku->value)->first();
        return $product ? ProductId::fromInt($product->id) : null;
    }

    public function getPointsAward(ProductId $product_id): int {
        $product = DB::table('products')->where('id', $product_id->toInt())->first();
        if (!$product) {
            return 0;
        }
        return (int) ($product->points_award ?? 0);
    }

    public function getPointsCost(ProductId $product_id): int {
        $product = DB::table('products')->where('id', $product_id->toInt())->first();
        if (!$product) {
            return 0;
        }
        return (int) ($product->points_cost ?? 0);
    }
    
    public function getRequiredRank(ProductId $product_id): ?string {
        $product = DB::table('products')->where('id', $product_id->toInt())->first();
        if (!$product) {
            return null;
        }
        $rank = $product->required_rank ?? '';
        return empty($rank) ? null : $rank;
    }
}