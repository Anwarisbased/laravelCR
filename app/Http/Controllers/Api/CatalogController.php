<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    // Backward compatibility methods
    public function getProducts(Request $request)
    {
        $user = $request->user();
        $products = $this->catalogService->getAllRewardProducts($user);
        
        // Format the products to match the expected API response format for backward compatibility
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'points_award' => $product->points_award,
                'points_cost' => $product->points_cost,
                'required_rank_key' => $product->required_rank_key,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'is_new' => $product->is_new,
                'brand' => $product->brand,
                'strain_type' => $product->strain_type,
                'thc_content' => $product->thc_content,
                'cbd_content' => $product->cbd_content,
                'product_form' => $product->product_form,
                'marketing_snippet' => $product->marketing_snippet,
                'images' => $product->image_urls,
                'tags' => $product->tags,
                'available_from' => $product->available_from,
                'available_until' => $product->available_until,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ];
            
            // Add eligibility info if the user is authenticated and the product has been checked
            if ($user && isset($product->eligibility)) {
                $formattedProduct['eligibility'] = $product->eligibility;
            }
            
            $formattedProducts[] = $formattedProduct;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'products' => $formattedProducts
            ]
        ]);
    }

    public function getProduct(Request $request, int $id)
    {
        $user = $request->user();
        $product = $this->catalogService->getProductWithEligibility($id, $user);
        
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        
        // Use the original format_product_for_api method to maintain compatibility
        $formattedProduct = $this->catalogService->format_product_for_api($product);
        
        // Add the eligibility for free claim based on ProductEligibilityService
        $formattedProduct['is_eligible_for_free_claim'] = $this->catalogService->is_user_eligible_for_free_claim($id, $user?->id ?? 0);
        
        return response()->json([
            'success' => true,
            'data' => $formattedProduct
        ]);
    }
    
    // New methods with full data format
    public function getProductsV2(Request $request)
    {
        $user = $request->user();
        $products = $this->catalogService->getAllRewardProducts($user);
        
        return new ProductCollection($products);
    }

    public function getProductV2(Request $request, int $id)
    {
        $user = $request->user();
        $product = $this->catalogService->getProductWithEligibility($id, $user);
        
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        
        return new ProductResource($product);
    }
    
    public function getCategories()
    {
        $categories = $this->catalogService->getCategories();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
    
    public function getFeaturedProducts(Request $request)
    {
        $user = $request->user();
        $products = $this->catalogService->getFeaturedProducts($user);
        
        // Format the products to match the expected API response format for backward compatibility
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'points_award' => $product->points_award,
                'points_cost' => $product->points_cost,
                'required_rank_key' => $product->required_rank_key,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'is_new' => $product->is_new,
                'brand' => $product->brand,
                'strain_type' => $product->strain_type,
                'thc_content' => $product->thc_content,
                'cbd_content' => $product->cbd_content,
                'product_form' => $product->product_form,
                'marketing_snippet' => $product->marketing_snippet,
                'images' => $product->image_urls,
                'tags' => $product->tags,
                'available_from' => $product->available_from,
                'available_until' => $product->available_until,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ];
            
            // Add eligibility info if the user is authenticated and the product has been checked
            if ($user && isset($product->eligibility)) {
                $formattedProduct['eligibility'] = $product->eligibility;
            }
            
            $formattedProducts[] = $formattedProduct;
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedProducts
        ]);
    }
    
    public function getNewProducts(Request $request)
    {
        $user = $request->user();
        $products = $this->catalogService->getNewProducts($user);
        
        // Format the products to match the expected API response format for backward compatibility
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'points_award' => $product->points_award,
                'points_cost' => $product->points_cost,
                'required_rank_key' => $product->required_rank_key,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'is_new' => $product->is_new,
                'brand' => $product->brand,
                'strain_type' => $product->strain_type,
                'thc_content' => $product->thc_content,
                'cbd_content' => $product->cbd_content,
                'product_form' => $product->product_form,
                'marketing_snippet' => $product->marketing_snippet,
                'images' => $product->image_urls,
                'tags' => $product->tags,
                'available_from' => $product->available_from,
                'available_until' => $product->available_until,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ];
            
            // Add eligibility info if the user is authenticated and the product has been checked
            if ($user && isset($product->eligibility)) {
                $formattedProduct['eligibility'] = $product->eligibility;
            }
            
            $formattedProducts[] = $formattedProduct;
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedProducts
        ]);
    }
}
