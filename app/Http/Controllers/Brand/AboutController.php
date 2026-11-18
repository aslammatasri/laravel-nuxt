<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use Illuminate\Http\Request;

// The public "who we are" text creators read on the brand page.
// Kept apart from verification so editing it never sends the brand back into review.
class AboutController extends Controller
{
    // GET /api/brand/about
    public function show(Request $request)
    {
        return response()->json(['about' => $request->user()->brandProfile?->about]);
    }

    // PUT /api/brand/about
    public function update(Request $request)
    {
        $data = $request->validate(['about' => 'nullable|string|max:1000']);
        $about = isset($data['about']) ? trim($data['about']) : null;

        BrandProfile::firstOrCreate(['user_id' => $request->user()->id])
            ->update(['about' => $about === '' ? null : $about]);

        return response()->json(['about' => $about === '' ? null : $about]);
    }
}
