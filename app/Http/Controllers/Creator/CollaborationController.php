<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    // GET /api/creator/collaborations
    public function index(Request $request)
    {
        $collabs = ProductApplication::with(['product.brand:id,name', 'product.variations'])
            ->where('creator_id', $request->user()->id)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->latest('updated_at')
            ->get()
            ->map(function ($a) {
                $p = $a->product;
                $end = $p->campaign_end;
                $daysLeft = $end ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;

                $state = match (true) {
                    $p->status === 'closed', $daysLeft !== null && $daysLeft < 0 => 'ended',
                    $daysLeft !== null && $daysLeft <= 14 => 'ending_soon',
                    default => 'active',
                };

                return [
                    'id'            => $a->id,
                    'approved_at'   => $a->updated_at,
                    'pitch_message' => $a->pitch_message,
                    'state'         => $state,
                    'days_left'     => $daysLeft,
                    'product'       => [
                        'id'              => $p->id,
                        'name'            => $p->name,
                        'description'     => $p->description,
                        'images'          => $p->images,
                        'category'        => $p->category,
                        'price'           => $p->price,
                        'commission_rate' => $p->commission_rate,
                        'commission_type' => $p->commission_type,
                        'campaign_start'  => $p->campaign_start,
                        'campaign_end'    => $p->campaign_end,
                        'status'          => $p->status,
                        'brand'           => ['name' => $p->brand?->name],
                        'variations'      => $p->variations->map(fn ($v) => [
                            'id'      => $v->id,
                            'name'    => $v->name,
                            'options' => $v->options,
                        ])->values(),
                    ],
                ];
            });

        return response()->json(['data' => $collabs]);
    }
}
