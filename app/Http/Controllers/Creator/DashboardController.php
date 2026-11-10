<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/creator/dashboard/stats
    public function stats(Request $request)
    {
        $user = $request->user();

        $applications = ProductApplication::with('product:id,name,category,campaign_end')
            ->where('creator_id', $user->id)
            ->get(['id', 'product_id', 'status', 'created_at']);

        $count = fn (ApplicationStatus $s) => $applications->where('status', $s)->count();
        $pending  = $count(ApplicationStatus::PENDING);
        $approved = $count(ApplicationStatus::APPROVED);
        $rejected = $count(ApplicationStatus::REJECTED);
        $decided  = $approved + $rejected;

        // Last 6 months, oldest first, zero-filled
        $months = collect(range(5, 0))->map(function ($ago) use ($applications) {
            $start = now()->startOfMonth()->subMonths($ago);
            return [
                'label' => $start->format('M'),
                'count' => $applications->filter(
                    fn ($a) => $a->created_at->isSameMonth($start) && $a->created_at->isSameYear($start)
                )->count(),
            ];
        })->values();

        $byCategory = $applications
            ->groupBy(fn ($a) => $a->product?->category ?: 'other')
            ->map(fn ($g, $cat) => ['category' => $cat, 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $endingSoon = $applications
            ->where('status', ApplicationStatus::APPROVED)
            ->filter(fn ($a) => $a->product?->campaign_end
                && $a->product->campaign_end->isBetween(now()->startOfDay(), now()->addDays(14)->endOfDay()))
            ->sortBy(fn ($a) => $a->product->campaign_end)
            ->take(5)
            ->map(fn ($a) => [
                'product_id'   => $a->product->id,
                'name'         => $a->product->name,
                'campaign_end' => $a->product->campaign_end->toDateString(),
                'days_left'    => (int) now()->startOfDay()->diffInDays($a->product->campaign_end->startOfDay()),
            ])
            ->values();

        $accounts = $user->creatorProfile?->socialAccounts ?? collect();

        return response()->json([
            'applications' => [
                'total'         => $applications->count(),
                'pending'       => $pending,
                'approved'      => $approved,
                'rejected'      => $rejected,
                'approval_rate' => $decided ? round($approved / $decided * 100) : null,
            ],
            'by_month'     => $months,
            'by_category'  => $byCategory,
            'ending_soon'  => $endingSoon,
            'social'       => [
                'accounts'         => $accounts->count(),
                'subscribers'      => (int) $accounts->sum('subscriber_count'),
                'avg_recent_views' => (int) $accounts->sum('avg_recent_views'),
                'engagement_rate'  => $accounts->whereNotNull('engagement_rate')->avg('engagement_rate'),
                'last_synced_at'   => $accounts->max('last_synced_at'),
            ],
        ]);
    }
}
