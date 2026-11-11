<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use App\Notifications\BrandVerificationUpdated;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    private const STATUSES = ['pending', 'verified', 'rejected'];

    // GET /api/admin/brands?status=pending&search=...
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $profiles = BrandProfile::with('user:id,name,email,created_at')
            ->when(in_array($status, self::STATUSES, true), fn ($q) => $q->where('verification_status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('company_name', 'like', "%{$search}%")
                ->orWhere('ssm_no', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->orderByRaw("verification_status = 'pending' desc")
            ->orderByDesc('submitted_at')
            ->paginate(20);

        $counts = BrandProfile::selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return response()->json([
            'data'   => $profiles->through(fn ($p) => $this->row($p))->items(),
            'total'  => $profiles->total(),
            'counts' => [
                'pending'  => (int) ($counts['pending'] ?? 0),
                'verified' => (int) ($counts['verified'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
        ]);
    }

    // PATCH /api/admin/brands/{id}/verify
    public function verify(int $id)
    {
        $profile = BrandProfile::with('user')->findOrFail($id);

        $profile->update([
            'verification_status' => 'verified',
            'is_verified'         => true,
            'verification_note'   => null,
            'verified_at'         => now(),
        ]);

        $profile->user->notify(new BrandVerificationUpdated($profile));

        return response()->json($this->row($profile));
    }

    // PATCH /api/admin/brands/{id}/reject  { reason }
    public function reject(int $id, Request $request)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);

        $profile = BrandProfile::with('user')->findOrFail($id);

        $profile->update([
            'verification_status' => 'rejected',
            'is_verified'         => false,
            'verification_note'   => $data['reason'],
            'verified_at'         => null,
        ]);

        $profile->user->notify(new BrandVerificationUpdated($profile));

        return response()->json($this->row($profile));
    }

    private function row(BrandProfile $p): array
    {
        return [
            'id'                  => $p->id,
            'company_name'        => $p->company_name,
            'industry'            => $p->industry,
            'ssm_no'              => $p->ssm_no,
            'verification_status' => $p->verification_status,
            'verification_note'   => $p->verification_note,
            'submitted_at'        => $p->submitted_at,
            'verified_at'         => $p->verified_at,
            'user'                => ['id' => $p->user->id, 'name' => $p->user->name, 'email' => $p->user->email],
        ];
    }
}
