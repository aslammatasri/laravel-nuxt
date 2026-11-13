<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\ProductApplication;
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

        // Latest application status per product for this creator
        $statuses = ProductApplication::where('creator_id', $request->user()->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->orderBy('id')
            ->pluck('status', 'product_id'); // later rows overwrite earlier ones
        $products->getCollection()->each(
            fn ($p) => $p->setAttribute('my_application_status', $statuses[$p->id]?->value ?? null)
        );

        return response()->json($products);
    }

    public function categories()
    {
        return response()->json(
            $this->productService->getCategories()
        );
    }

    public function show(int $id, Request $request)
    {
        $product = $this->productService->getProductDetail($id);
        $product->load('brand.brandProfile:id,user_id,is_verified');

        // Latest application by this creator, so the UI can block re-applying
        $latest = ProductApplication::where('creator_id', $request->user()->id)
            ->where('product_id', $id)
            ->latest('id')
            ->value('status');
        $product->setAttribute('my_application_status', $latest?->value);
        $product->setAttribute(
            'my_subscribers',
            (int) ($request->user()->creatorProfile?->socialAccounts()->sum('subscriber_count') ?? 0)
        );

        return response()->json($product);
    }
}