<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandReview;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;

class BrandController extends Controller
{
    // GET /api/creator/brands/{id}
    // Public subset of a brand. Never expose email, SSM number or verification notes.
    public function show(int $id)
    {
        $brand = User::with('brandProfile')
            ->where('role', 'brand')
            ->whereNull('suspended_at')
            ->findOrFail($id);

        $campaigns = Product::where('brand_id', $brand->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('campaign_end')->orWhere('campaign_end', '>=', now()))
            ->latest()
            ->get(['id', 'name', 'images', 'flat_fee', 'free_sample', 'platforms', 'campaign_end']);

        $creatorsWorkedWith = ProductApplication::where('status', ApplicationStatus::APPROVED->value)
            ->whereHas('product', fn ($q) => $q->where('brand_id', $brand->id))
            ->distinct('creator_id')
            ->count('creator_id');

        $reviews = BrandReview::with(['creator:id,name,avatar', 'application.product:id,name'])
            ->where('brand_id', $brand->id)
            ->latest()
            ->limit(30)
            ->get();
        $stats = BrandReview::where('brand_id', $brand->id)->selectRaw('count(*) as n, avg(rating) as avg')->first();

        return response()->json(['data' => [
            'id'                   => $brand->id,
            'name'                 => $brand->name,
            'avatar'               => $brand->avatar,
            'company_name'         => $brand->brandProfile?->company_name,
            'industry'             => $brand->brandProfile?->industry,
            'about'                => $brand->brandProfile?->about,
            'is_verified'          => (bool) $brand->brandProfile?->is_verified,
            'member_since'         => $brand->created_at,
            'creators_worked_with' => $creatorsWorkedWith,
            'rating_average'       => $stats->n ? round((float) $stats->avg, 1) : null,
            'rating_count'         => (int) $stats->n,
            'reviews'              => $reviews->map(fn ($r) => [
                'id'         => $r->id,
                'rating'     => $r->rating,
                'body'       => $r->body,
                'created_at' => $r->created_at,
                'campaign'   => $r->application?->product?->name,
                'creator'    => ['name' => $r->creator?->name, 'avatar' => $r->creator?->avatar],
                'mine'       => $r->creator_id === request()->user()->id,
            ])->values(),
            'campaigns'            => $campaigns->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'image'       => $p->images[0] ?? null,
                'flat_fee'    => $p->flat_fee,
                'free_sample' => $p->free_sample,
                'platforms'   => $p->platforms,
                'campaign_end' => $p->campaign_end?->toDateString(),
            ])->values(),
        ]]);
    }
}
