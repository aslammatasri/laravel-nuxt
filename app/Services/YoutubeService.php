<?php

namespace App\Services;

use App\Models\CreatorSocialAccount;
use Illuminate\Support\Facades\Http;

class YoutubeService
{
    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    private const RECENT_VIDEO_COUNT = 10;

    public function resolveChannelById(string $channelId): array
    {
        $response = Http::get(self::BASE_URL . '/channels', [
            'part' => 'snippet,statistics,contentDetails',
            'id'   => $channelId,
            'key'  => config('services.youtube.key'),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Unable to reach YouTube API', 502);
        }

        $items = $response->json('items', []);

        if (empty($items)) {
            throw new \Exception('YouTube channel not found', 404);
        }

        return $this->mapChannelItem($items[0]);
    }

    public function resolveOwnedChannel(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::BASE_URL . '/channels', [
            'part' => 'snippet,statistics,contentDetails',
            'mine' => 'true',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Unable to reach YouTube API', 502);
        }

        $items = $response->json('items', []);

        if (empty($items)) {
            throw new \Exception('No YouTube channel found on this Google account', 404);
        }

        return $this->mapChannelItem($items[0]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => 'postmessage',
            'grant_type'    => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Unable to verify Google account', 502);
        }

        return $response->json();
    }

    public function connectAndVerify(string $code): array
    {
        $token = $this->exchangeCodeForToken($code);

        $channel = $this->resolveOwnedChannel($token['access_token']);

        return $channel + ['google_email' => $this->extractEmail($token['id_token'] ?? null)];
    }

    private function extractEmail(?string $idToken): ?string
    {
        if (! $idToken || substr_count($idToken, '.') !== 2) {
            return null;
        }

        [, $payload] = explode('.', $idToken);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        return $claims['email'] ?? null;
    }

    private function mapChannelItem(array $channel): array
    {
        return [
            'channel_id'          => $channel['id'],
            'handle'              => isset($channel['snippet']['customUrl'])
                ? ltrim($channel['snippet']['customUrl'], '@')
                : null,
            'title'               => $channel['snippet']['title'] ?? null,
            'thumbnail_url'       => $channel['snippet']['thumbnails']['default']['url'] ?? null,
            'subscriber_count'    => (int) ($channel['statistics']['subscriberCount'] ?? 0),
            'view_count'          => (int) ($channel['statistics']['viewCount'] ?? 0),
            'video_count'         => (int) ($channel['statistics']['videoCount'] ?? 0),
            'uploads_playlist_id' => $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null,
        ];
    }

    public function fetchRecentEngagement(string $uploadsPlaylistId): array
    {
        $playlistResponse = Http::get(self::BASE_URL . '/playlistItems', [
            'part'       => 'contentDetails',
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => self::RECENT_VIDEO_COUNT,
            'key'        => config('services.youtube.key'),
        ]);

        if (! $playlistResponse->successful()) {
            throw new \Exception('Unable to reach YouTube API', 502);
        }

        $videoIds = collect($playlistResponse->json('items', []))
            ->pluck('contentDetails.videoId')
            ->filter()
            ->implode(',');

        if ($videoIds === '') {
            return [
                'avg_recent_views'    => null,
                'avg_recent_likes'    => null,
                'avg_recent_comments' => null,
                'engagement_rate'     => null,
            ];
        }

        $videosResponse = Http::get(self::BASE_URL . '/videos', [
            'part' => 'statistics',
            'id'   => $videoIds,
            'key'  => config('services.youtube.key'),
        ]);

        if (! $videosResponse->successful()) {
            throw new \Exception('Unable to reach YouTube API', 502);
        }

        $videos = collect($videosResponse->json('items', []));

        $avgViews    = (int) round($videos->avg(fn ($v) => (int) ($v['statistics']['viewCount'] ?? 0)));
        $avgLikes    = (int) round($videos->avg(fn ($v) => (int) ($v['statistics']['likeCount'] ?? 0)));
        $avgComments = (int) round($videos->avg(fn ($v) => (int) ($v['statistics']['commentCount'] ?? 0)));

        return [
            'avg_recent_views'    => $avgViews,
            'avg_recent_likes'    => $avgLikes,
            'avg_recent_comments' => $avgComments,
            'engagement_rate'     => $avgViews > 0
                ? round((($avgLikes + $avgComments) / $avgViews) * 100, 2)
                : null,
        ];
    }

    public function syncAccount(CreatorSocialAccount $account): CreatorSocialAccount
    {
        try {
            $channel = $this->resolveChannelById($account->channel_id);
            $engagement = $channel['uploads_playlist_id']
                ? $this->fetchRecentEngagement($channel['uploads_playlist_id'])
                : [];

            $account->fill([
                'channel_id'          => $channel['channel_id'],
                'title'               => $channel['title'],
                'thumbnail_url'       => $channel['thumbnail_url'],
                'subscriber_count'    => $channel['subscriber_count'],
                'view_count'          => $channel['view_count'],
                'video_count'         => $channel['video_count'],
                'avg_recent_views'    => $engagement['avg_recent_views'] ?? null,
                'avg_recent_likes'    => $engagement['avg_recent_likes'] ?? null,
                'avg_recent_comments' => $engagement['avg_recent_comments'] ?? null,
                'engagement_rate'     => $engagement['engagement_rate'] ?? null,
                'last_synced_at'      => now(),
                'sync_error'          => null,
            ]);
            $account->save();

            return $account;
        } catch (\Exception $e) {
            $account->update(['sync_error' => $e->getMessage()]);

            throw $e;
        }
    }
}
