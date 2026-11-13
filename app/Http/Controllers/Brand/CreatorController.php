<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Enums\ApplicationStatus;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Http\Request;

class CreatorController extends Controller
{
    // GET /api/brand/creators
    // Creators approved on this brand's campaigns, with checklist progress per campaign
    public function index(Request $request)
    {
        $apps = ProductApplication::with(['creator:id,name,email,avatar', 'product:id,name,images,campaign_end,status', 'tasks'])
            ->whereHas('product', fn ($q) => $q->where('brand_id', $request->user()->id))
            ->where('status', ApplicationStatus::APPROVED->value)
            ->latest('updated_at')
            ->get();

        $creators = $apps->groupBy('creator_id')->map(function ($group) {
            $creator = $group->first()->creator;

            return [
                'id'     => $creator->id,
                'name'   => $creator->name,
                'email'  => $creator->email,
                'avatar' => $creator->avatar,
                'collaborations' => $group->map(function ($a) {
                    $end = $a->product->campaign_end;
                    $ended = $a->product->status === 'closed' || ($end && $end->isPast() && ! $end->isToday());

                    return [
                        'id'       => $a->id,
                        'product'  => ['id' => $a->product->id, 'name' => $a->product->name, 'images' => $a->product->images],
                        'ended'    => $ended,
                        'tasks'    => $a->tasks->map(fn ($t) => [
                            'id'       => $t->id,
                            'title'    => $t->title,
                            'type'     => $t->type,
                            'due_date' => $t->due_date?->toDateString(),
                            'link'     => $t->link,
                            'done'     => $t->completed_at !== null,
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['data' => $creators]);
    }

    // GET /api/brand/creators/{id}/portfolio
    public function portfolio(int $id)
    {
        $creator = User::with('creatorProfile.socialAccounts')->findOrFail($id);

        return response()->json([
            'id'              => $creator->id,
            'name'            => $creator->name,
            'email'           => $creator->email,
            'avatar'          => $creator->avatar,
            'social_accounts' => $creator->creatorProfile?->socialAccounts ?? [],
        ]);
    }
}
