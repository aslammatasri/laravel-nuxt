<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'category', 'commission_type',
            'min_commission', 'max_commission',
            'min_price', 'max_price', 'sort',
        ]);
        $products = $this->productService->getMarketplaceProducts($filters);

        return response()->json($products);
    }

    public function categories()
    {
        return response()->json(
            $this->productService->getCategories()
        );
    }

    public function show(int $id)
    {
        $product = $this->productService->getProductDetail($id);

        return response()->json($product);
    }
}