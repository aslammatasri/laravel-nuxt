<?php

namespace App\Http\Controllers\Brand;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
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
            ->withCount('applications')
            ->having('applications_count', '>', 0)
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'applications' => $p->applications_count]);

        return response()->json([
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
