<?php

namespace App\Http\Controllers\Brand;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandReview;
use App\Models\CollaborationTask;
use App\Models\Product;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/brand/dashboard/stats
    public function stats(Request $request)
    {
        $brandId = $request->user()->id;

        $applications = ProductApplication::whereHas('product', fn ($q) => $q->where('brand_id', $brandId));

        $byStatus = (clone $applications)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending  = (int) ($byStatus[ApplicationStatus::PENDING->value] ?? 0);
        $approved = (int) ($byStatus[ApplicationStatus::APPROVED->value] ?? 0);
        $rejected = (int) ($byStatus[ApplicationStatus::REJECTED->value] ?? 0);
        $total    = $pending + $approved + $rejected;
        $decided  = $approved + $rejected;

        $products = Product::where('brand_id', $brandId);
        $activeProducts = (clone $products)->where('status', 'active')->count();
        $totalProducts  = (clone $products)->count();

        $topProducts = Product::where('brand_id', $brandId)
            ->whereHas('applications')
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'applications' => $p->applications_count]);

        // Approved collaborations drive the money and the to-do numbers
        $collabs = ProductApplication::with(['product:id,brand_id,name,flat_fee,status,campaign_end', 'tasks'])
            ->whereHas('product', fn ($q) => $q->where('brand_id', $brandId))
            ->where('status', ApplicationStatus::APPROVED->value)
            ->get();

        $feeOf = fn ($a) => (float) $a->product->flat_fee;
        $due = $collabs->filter(fn ($a) => $a->feeStatus() === 'due');
        $awaiting = $collabs->filter(fn ($a) => $a->feeStatus() === 'pending');
        $paid = $collabs->filter(fn ($a) => $a->paid_at !== null);

        $deliverablesToReview = CollaborationTask::where('type', '!=', 'sample')
            ->whereNotNull('completed_at')
            ->where('review_status', 'pending')
            ->whereHas('application', fn ($q) => $q
                ->where('status', ApplicationStatus::APPROVED->value)
                ->whereHas('product', fn ($p) => $p->where('brand_id', $brandId)))
            ->count();

        $today = now()->startOfDay();
        $overdue = $collabs->filter(function ($a) use ($today) {
            $end = $a->product->campaign_end;
            $ended = $a->product->status === 'closed' || ($end && $end->copy()->startOfDay()->lt($today));

            return ! $ended && $a->tasks->contains(fn ($t) => $t->completed_at === null && $t->due_date && $t->due_date->copy()->startOfDay()->lt($today));
        });

        $reviewStats = BrandReview::where('brand_id', $brandId)->selectRaw('count(*) as n, avg(rating) as avg')->first();
        $latestReview = BrandReview::with(['creator:id,name', 'application.product:id,name'])
            ->where('brand_id', $brandId)->latest()->first();

        $profile = $request->user()->brandProfile;

        return response()->json([
            'actions' => [
                'pending_applications'   => $pending,
                'deliverables_to_review' => $deliverablesToReview,
                'fees_due'               => ['count' => $due->count(), 'amount' => round($due->sum($feeOf), 2)],
                'overdue_creators'       => $overdue->count(),
            ],
            'spend' => [
                'due'              => round($due->sum($feeOf), 2),
                'awaiting'         => round($awaiting->sum($feeOf), 2),
                'paid_this_month'  => round($paid->filter(fn ($a) => $a->paid_at->gte(now()->startOfMonth()))->sum($feeOf), 2),
                'paid_total'       => round($paid->sum($feeOf), 2),
            ],
            'rating' => [
                'average' => $reviewStats->n ? round((float) $reviewStats->avg, 1) : null,
                'count'   => (int) $reviewStats->n,
                'latest'  => $latestReview ? [
                    'rating'   => $latestReview->rating,
                    'body'     => $latestReview->body,
                    'creator'  => $latestReview->creator?->name,
                    'campaign' => $latestReview->application?->product?->name,
                ] : null,
            ],
            'setup' => [
                'has_about'    => filled($profile?->about),
                'has_campaign' => $totalProducts > 0,
                'has_creator'  => $approved > 0,
            ],
            'applications' => [
                'total'         => $total,
                'pending'       => $pending,
                'approved'      => $approved,
                'rejected'      => $rejected,
                'approval_rate' => $decided ? round($approved / $decided * 100) : null,
            ],
            'products' => [
                'total'    => $totalProducts,
                'active'   => $activeProducts,
                'inactive' => $totalProducts - $activeProducts,
            ],
            'top_products' => $topProducts,
        ]);
    }
}
