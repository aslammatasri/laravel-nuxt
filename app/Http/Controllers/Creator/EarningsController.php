<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Brand\SaleController;
use App\Http\Controllers\Controller;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    // GET /api/creator/earnings
    // Flat fees are the live model. The `commission` block (clicks, sales) is kept for when sales tracking returns.
    public function index(Request $request)
    {
        $apps = ProductApplication::with(['product:id,name,flat_fee,commission_rate,commission_type', 'tasks', 'sales', 'clicks:id,application_id'])
            ->where('creator_id', $request->user()->id)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->get();

        $campaigns = $apps->map(fn ($a) => [
            'id'         => $a->id,
            'name'       => $a->product->name,
            'fee'        => $a->feeSummary(),
            'commission' => [
                'tracking_url' => $a->trackingUrl(),
                'clicks'       => $a->clicks->count(),
            ] + $a->salesSummary() + [
                'sales' => $a->sales->sortByDesc('sold_at')->map(fn ($s) => SaleController::present($s))->values(),
            ],
        ])->values();

        $fees = $campaigns->pluck('fee');

        return response()->json([
            'totals' => [
                'due'     => round($fees->where('status', 'due')->sum('amount'), 2),
                'pending' => round($fees->where('status', 'pending')->sum('amount'), 2),
                'paid'    => round($fees->where('status', 'paid')->sum('amount'), 2),
            ],
            'campaigns' => $campaigns,
        ]);
    }
}
