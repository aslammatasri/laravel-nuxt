<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\BrandReview;
use Illuminate\Http\Request;

// Read-only: the reviews creators have left about this brand
class BrandReviewController extends Controller
{
    // GET /api/brand/reviews
    public function index(Request $request)
    {
        $brandId = $request->user()->id;

        $counts = BrandReview::where('brand_id', $brandId)
            ->selectRaw('rating, count(*) as n')
            ->groupBy('rating')
            ->pluck('n', 'rating');
        $count = (int) $counts->sum();
        $average = $count ? round($counts->map(fn ($n, $r) => $n * $r)->sum() / $count, 1) : null;

        $reviews = BrandReview::with(['creator:id,name,avatar', 'application.product:id,name'])
            ->where('brand_id', $brandId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'rating'     => $r->rating,
                'body'       => $r->body,
                'created_at' => $r->created_at,
                'campaign'   => $r->application?->product?->name,
                'creator'    => ['id' => $r->creator?->id, 'name' => $r->creator?->name, 'avatar' => $r->creator?->avatar],
            ])->values();

        return response()->json([
            'average'      => $average,
            'count'        => $count,
            'distribution' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)]),
            'data'         => $reviews,
        ]);
    }
}
