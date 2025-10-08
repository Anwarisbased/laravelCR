<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Data\Catalog\ProductData;
use App\Data\Catalog\CategoryData;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    // Backward compatibility methods
    public function getProducts(Request $request)
    {
        $user = $request->user();
        $productModels = $this->catalogService->getAllRewardProducts($user);
        
        // Convert Eloquent models to Laravel Data objects
        $productsData = $productModels->map(function ($productModel) {
            return \App\Data\Catalog\ProductData::fromModel($productModel);
        });
        
        return response()->json([
            'products' => $productsData
        ]);
    }

    public function getProduct(Request $request, int $id)
    {
        $user = $request->user();
        $productModel = $this->catalogService->getProductWithEligibility($id, $user);
        
        if (!$productModel) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        
        // Convert the Eloquent model to a Laravel Data object
        $productData = \App\Data\Catalog\ProductData::fromModel($productModel);
        
        return response()->json($productData);
    }
    

    
    public function getCategories()
    {
        $categoryModels = $this->catalogService->getCategories();
        
        $categoriesData = $categoryModels->map(function ($categoryModel) {
            return \App\Data\Catalog\CategoryData::fromModel($categoryModel);
        });
        
        return response()->json([
            'categories' => $categoriesData
        ]);
    }
    
    public function getFeaturedProducts(Request $request)
    {
        $user = $request->user();
        $productModels = $this->catalogService->getFeaturedProducts($user);
        
        // Convert Eloquent models to Laravel Data objects
        $productsData = $productModels->map(function ($productModel) {
            return \App\Data\Catalog\ProductData::fromModel($productModel);
        });
        
        return response()->json([
            'products' => $productsData
        ]);
    }
    
    public function getNewProducts(Request $request)
    {
        $user = $request->user();
        $productModels = $this->catalogService->getNewProducts($user);
        
        // Convert Eloquent models to Laravel Data objects
        $productsData = $productModels->map(function ($productModel) {
            return \App\Data\Catalog\ProductData::fromModel($productModel);
        });
        
        return response()->json([
            'products' => $productsData
        ]);
    }
}
