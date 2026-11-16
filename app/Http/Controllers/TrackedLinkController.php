<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\LinkClick;
use App\Models\ProductApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackedLinkController extends Controller
{
    // GET /r/{code}: log the click, then send the visitor to the brand's product page
    public function __invoke(Request $request, string $code)
    {
        $application = ProductApplication::with('product:id,product_url,status')
            ->where('tracking_code', $code)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->firstOrFail();

        $url = $application->product->product_url;
        abort_unless($url && preg_match('#^https?://#i', $url), 404);

        $ipHash = hash('sha256', $request->ip() . config('app.key'));

        // Count one click per visitor per 30 minutes so reloads do not inflate numbers
        $recent = LinkClick::where('application_id', $application->id)
            ->where('ip_hash', $ipHash)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if (! $recent) {
            LinkClick::create([
                'application_id' => $application->id,
                'ip_hash'        => $ipHash,
                'user_agent'     => Str::limit((string) $request->userAgent(), 250, ''),
                'referrer'       => Str::limit((string) $request->headers->get('referer'), 250, ''),
            ]);
        }

        return redirect()->away($url);
    }
}
