<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreProductRequest;
use App\Http\Requests\Brand\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    // GET /api/brand/products
    public function index(Request $request)
    {
        $products = $this->productService->getBrandProducts(
            $request->user()->id
        );

        return response()->json($products);
    }

    // POST /api/brand/products
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct(
            $request->validated(),
            $request->user()->id,
            $request->file('images', [])
        );

        return response()->json($product, 201);
    }

    // GET /api/brand/products/{id}
    public function show(int $id, Request $request)
    {
        $product = $this->productService->getBrandProduct(
            $id,
            $request->user()->id
        );

        return response()->json($product);
    }

    // PUT /api/brand/products/{id}
    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->productService->updateProduct(
            $id,
            $request->validated(),
            $request->user()->id,
            $request->file('images', []),
            $request->input('delete_images', [])
        );

        return response()->json($product);
    }

    // DELETE /api/brand/products/{id}
    public function destroy(int $id, Request $request)
    {
        $this->productService->deleteProduct(
            $id,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}