<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class VariationController extends Controller
{
    public function store(Request $request, int $productId)
    {
        $product = Product::findOrFail($productId);

        if ($product->brand_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'options'            => 'required|array|min:1',
            'options.*.value'    => 'required|string|max:100',
            'options.*.price'    => 'required|numeric|min:0',
        ]);

        $variation = $product->variations()->create($data);

        return response()->json($variation, 201);
    }

    public function update(Request $request, int $productId, int $variationId)
    {
        $product = Product::findOrFail($productId);

        if ($product->brand_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $variation = $product->variations()->findOrFail($variationId);

        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'options'            => 'required|array|min:1',
            'options.*.value'    => 'required|string|max:100',
            'options.*.price'    => 'required|numeric|min:0',
        ]);

        $variation->update($data);

        return response()->json($variation);
    }

    public function destroy(Request $request, int $productId, int $variationId)
    {
        $product = Product::findOrFail($productId);

        if ($product->brand_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->variations()->findOrFail($variationId)->delete();

        return response()->json(['message' => 'Variation deleted']);
    }
}
