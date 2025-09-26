<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    public function getProducts()
    {
        $products = $this->catalogService->get_all_reward_products();
        return response()->json(['success' => true, 'data' => ['products' => $products]]);
    }

    public function getProduct(Request $request, int $id)
    {
        $userId = $request->user() ? $request->user()->id : 0;
        $product = $this->catalogService->get_product_with_eligibility($id, $userId);
        return response()->json(['success' => true, 'data' => $product]);
    }
}
