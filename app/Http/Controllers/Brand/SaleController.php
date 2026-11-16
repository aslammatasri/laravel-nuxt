<?php

namespace App\Http\Controllers\Brand;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductApplication;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    // POST /api/brand/applications/{id}/sales
    public function store(Request $request, int $id)
    {
        $application = ProductApplication::with('product')
            ->whereHas('product', fn ($q) => $q->where('brand_id', $request->user()->id))
            ->where('status', ApplicationStatus::APPROVED->value)
            ->findOrFail($id);

        $data = $request->validate([
            'amount'    => 'required|numeric|min:0.01|max:99999999',
            'order_ref' => 'nullable|string|max:60',
            'sold_at'   => 'nullable|date|before_or_equal:today',
        ]);

        $sale = $application->sales()->create([
            'amount'     => $data['amount'],
            'commission' => $application->product->commissionFor((float) $data['amount']),
            'order_ref'  => $data['order_ref'] ?? null,
            'sold_at'    => $data['sold_at'] ?? now()->toDateString(),
            'status'     => Sale::APPROVED,
        ]);

        return response()->json(['data' => self::present($sale)], 201);
    }

    // PATCH /api/brand/sales/{id}/paid
    public function markPaid(Request $request, int $id)
    {
        $sale = $this->own($request, $id);
        abort_unless($sale->status === Sale::APPROVED, 422, 'Only an approved sale can be marked paid.');

        $sale->update(['status' => Sale::PAID, 'paid_at' => now()]);

        return response()->json(['data' => self::present($sale)]);
    }

    // PATCH /api/brand/sales/{id}/reverse (refund or return)
    public function reverse(Request $request, int $id)
    {
        $sale = $this->own($request, $id);
        abort_unless($sale->status !== Sale::REVERSED, 422, 'This sale is already reversed.');

        $sale->update(['status' => Sale::REVERSED]);

        return response()->json(['data' => self::present($sale)]);
    }

    private function own(Request $request, int $id): Sale
    {
        return Sale::whereHas('application.product', fn ($q) => $q->where('brand_id', $request->user()->id))
            ->findOrFail($id);
    }

    public static function present(Sale $s): array
    {
        return [
            'id'         => $s->id,
            'order_ref'  => $s->order_ref,
            'amount'     => (float) $s->amount,
            'commission' => (float) $s->commission,
            'status'     => $s->status,
            'sold_at'    => $s->sold_at->toDateString(),
            'paid_at'    => $s->paid_at,
        ];
    }
}
