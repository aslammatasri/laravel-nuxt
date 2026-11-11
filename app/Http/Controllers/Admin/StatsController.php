<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;

class StatsController extends Controller
{
    // GET /api/admin/stats
    public function index()
    {
        $count = fn ($query, string $col) => $query->selectRaw("{$col} as k, count(*) as total")->groupBy($col)->pluck('total', 'k');

        $apps     = $count(ProductApplication::query(), 'status');
        $products = $count(Product::query(), 'status');
        $brands   = $count(BrandProfile::query(), 'verification_status');

        // Signups per month, last 6 months, oldest first, zero-filled
        $recent = User::whereIn('role', ['creator', 'brand'])
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['role', 'created_at']);

        $signups = collect(range(5, 0))->map(function ($ago) use ($recent) {
            $start = now()->startOfMonth()->subMonths($ago);
            $month = $recent->filter(fn ($u) => $u->created_at->isSameMonth($start) && $u->created_at->isSameYear($start));

            return [
                'label'    => $start->format('M'),
                'creators' => $month->where('role', 'creator')->count(),
                'brands'   => $month->where('role', 'brand')->count(),
            ];
        })->values();

        $brandUsers = User::where('role', 'brand')->count();
        $submitted  = (int) ($brands['pending'] ?? 0) + (int) ($brands['verified'] ?? 0) + (int) ($brands['rejected'] ?? 0);

        return response()->json([
            'users' => [
                'creators'  => User::where('role', 'creator')->count(),
                'brands'    => $brandUsers,
                'suspended' => User::whereIn('role', ['creator', 'brand'])->whereNotNull('suspended_at')->count(),
            ],
            'verification' => [
                'pending'     => (int) ($brands['pending'] ?? 0),
                'verified'    => (int) ($brands['verified'] ?? 0),
                'rejected'    => (int) ($brands['rejected'] ?? 0),
                'not_started' => max(0, $brandUsers - $submitted),
            ],
            'products' => [
                'active' => (int) ($products['active'] ?? 0),
                'paused' => (int) ($products['paused'] ?? 0),
                'closed' => (int) ($products['closed'] ?? 0),
                'total'  => (int) $products->sum(),
            ],
            'applications' => [
                'pending'  => (int) ($apps['pending'] ?? 0),
                'approved' => (int) ($apps['approved'] ?? 0),
                'rejected' => (int) ($apps['rejected'] ?? 0),
                'total'    => (int) $apps->sum(),
            ],
            'signups' => $signups,
        ]);
    }
}
