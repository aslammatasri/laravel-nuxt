<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PayoutDetailsController extends Controller
{
    private const FIELDS = ['payout_bank', 'payout_account_name', 'payout_account_number'];

    // GET /api/creator/payout-details
    public function show(Request $request)
    {
        $p = $request->user()->creatorProfile;

        return response()->json(['data' => [
            'bank'           => $p?->payout_bank,
            'account_name'   => $p?->payout_account_name,
            'account_number' => $p?->payout_account_number,
        ]]);
    }

    // PUT /api/creator/payout-details
    public function update(Request $request)
    {
        $data = $request->validate([
            'bank'           => 'required|string|max:80',
            'account_name'   => 'required|string|max:120',
            'account_number' => 'required|string|max:40',
        ]);

        $profile = $request->user()->creatorProfile()->firstOrCreate([]);
        $profile->update([
            'payout_bank'           => $data['bank'],
            'payout_account_name'   => $data['account_name'],
            'payout_account_number' => $data['account_number'],
        ]);

        return $this->show($request);
    }
}
