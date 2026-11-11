<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /api/admin/users?role=creator|brand&status=suspended|active&search=
    public function index(Request $request)
    {
        $role   = $request->query('role');
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->whereIn('role', ['creator', 'brand'])
            ->when(in_array($role, ['creator', 'brand'], true), fn ($q) => $q->where('role', $role))
            ->when($status === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($status === 'active', fn ($q) => $q->whereNull('suspended_at'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data'  => $users->through(fn ($u) => $this->row($u))->items(),
            'total' => $users->total(),
            'counts' => [
                'creators'  => User::where('role', 'creator')->count(),
                'brands'    => User::where('role', 'brand')->count(),
                'suspended' => User::whereIn('role', ['creator', 'brand'])->whereNotNull('suspended_at')->count(),
            ],
        ]);
    }

    // PATCH /api/admin/users/{id}/suspend  { reason }
    public function suspend(int $id, Request $request)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);

        $user = $this->findManageable($id);

        $user->forceFill([
            'suspended_at'     => now(),
            'suspended_reason' => $data['reason'],
        ])->save();

        // Sign the user out everywhere right away
        $user->tokens()->delete();

        return response()->json($this->row($user));
    }

    // PATCH /api/admin/users/{id}/reactivate
    public function reactivate(int $id)
    {
        $user = $this->findManageable($id);

        $user->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();

        return response()->json($this->row($user));
    }

    // Admins cannot be suspended through this API
    private function findManageable(int $id): User
    {
        $user = User::findOrFail($id);

        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be managed here');

        return $user;
    }

    private function row(User $u): array
    {
        return [
            'id'               => $u->id,
            'name'             => $u->name,
            'email'            => $u->email,
            'role'             => $u->role,
            'created_at'       => $u->created_at,
            'suspended_at'     => $u->suspended_at,
            'suspended_reason' => $u->suspended_reason,
        ];
    }
}
