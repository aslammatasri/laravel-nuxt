<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\ConnectYoutubeRequest;
use App\Models\CreatorSocialAccount;
use App\Services\YoutubeService;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function __construct(
        private YoutubeService $youtubeService
    ) {}

    // GET /api/creator/social-accounts
    public function index(Request $request)
    {
        $creatorProfile = $request->user()->creatorProfile;

        $accounts = $creatorProfile ? $creatorProfile->socialAccounts : [];

        return response()->json(['data' => $accounts]);
    }

    // POST /api/creator/social-accounts/youtube
    public function store(ConnectYoutubeRequest $request)
    {
        $creatorProfile = $request->user()->creatorProfile()->firstOrCreate([]);

        try {
            $verified = $this->youtubeService->connectAndVerify($request->validated()['code']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        $alreadyClaimed = CreatorSocialAccount::where('channel_id', $verified['channel_id'])
            ->where('creator_profile_id', '!=', $creatorProfile->id)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json(['message' => 'This channel is already linked to another creator account'], 422);
        }

        try {
            $account = CreatorSocialAccount::updateOrCreate(
                ['creator_profile_id' => $creatorProfile->id, 'platform' => 'youtube'],
                [
                    'channel_id'   => $verified['channel_id'],
                    'handle'       => $verified['handle'],
                    'google_email' => $verified['google_email'],
                    'verified_at'  => now(),
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'This channel is already linked to another creator account'], 422);
        }

        try {
            $this->youtubeService->syncAccount($account);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json($account);
    }

    // POST /api/creator/social-accounts/{id}/sync
    public function sync(int $id, Request $request)
    {
        $creatorProfile = $request->user()->creatorProfile;

        $account = $creatorProfile?->socialAccounts()->find($id);

        if (! $account) {
            return response()->json(['message' => 'Social account not found'], 404);
        }

        try {
            $this->youtubeService->syncAccount($account);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json($account);
    }

    // DELETE /api/creator/social-accounts/{id}
    public function destroy(int $id, Request $request)
    {
        $creatorProfile = $request->user()->creatorProfile;

        $account = $creatorProfile?->socialAccounts()->find($id);

        if (! $account) {
            return response()->json(['message' => 'Social account not found'], 404);
        }

        $account->delete();

        return response()->json(null, 204);
    }
}
