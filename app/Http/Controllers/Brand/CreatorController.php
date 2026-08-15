<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\User;

class CreatorController extends Controller
{
    // GET /api/brand/creators/{id}/portfolio
    public function portfolio(int $id)
    {
        $creator = User::with('creatorProfile.socialAccounts')->findOrFail($id);

        return response()->json([
            'id'              => $creator->id,
            'name'            => $creator->name,
            'email'           => $creator->email,
            'social_accounts' => $creator->creatorProfile?->socialAccounts ?? [],
        ]);
    }
}
