<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    // GET /api/brand/verification
    public function show(Request $request)
    {
        return response()->json($this->payload($request->user()->brandProfile));
    }

    // PUT /api/brand/verification
    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'industry'     => 'nullable|string|max:100',
            'ssm_no'       => ['required', 'string', 'regex:/^[A-Za-z0-9\-\/]{5,20}$/'],
        ], [
            'ssm_no.regex' => 'Enter a valid SSM registration number (letters, numbers, - or /).',
        ]);

        $profile = BrandProfile::firstOrNew(['user_id' => $request->user()->id]);

        $identityChanged = ! $profile->exists
            || $profile->company_name !== $data['company_name']
            || $profile->ssm_no !== $data['ssm_no'];

        $profile->fill($data);

        // New details, or any change to what was verified, go back into review
        if ($identityChanged || $profile->verification_status !== 'verified') {
            $profile->verification_status = 'pending';
            $profile->verification_note   = null;
            $profile->is_verified         = false;
            $profile->verified_at         = null;
            $profile->submitted_at        = now();
        }

        $profile->save();

        return response()->json($this->payload($profile));
    }

    private function payload(?BrandProfile $profile): array
    {
        return [
            'company_name'        => $profile?->company_name,
            'industry'            => $profile?->industry,
            'ssm_no'              => $profile?->ssm_no,
            'verification_status' => $profile?->verification_status ?? 'unverified',
            'verification_note'   => $profile?->verification_note,
            'submitted_at'        => $profile?->submitted_at,
            'verified_at'         => $profile?->verified_at,
        ];
    }
}
