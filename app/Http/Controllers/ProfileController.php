<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Name updated successfully',
            'user'    => $user->fresh(),
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'avatar.required' => 'Choose a photo to upload.',
            'avatar.image'    => 'Only image files are allowed.',
            'avatar.mimes'    => 'The photo must be a JPG, PNG or WEBP image.',
            'avatar.max'      => 'The photo must be 2 MB or smaller.',
            'avatar.uploaded' => 'The photo failed to upload. It is probably larger than 2 MB.',
        ]);

        $user = $request->user();
        $old  = $user->avatar;

        $user->forceFill(['avatar' => $request->file('avatar')->store('avatars', 'public')])->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return response()->json([
            'message' => 'Profile photo updated',
            'user'    => $user->fresh(),
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->forceFill(['avatar' => null])->save();
        }

        return response()->json([
            'message' => 'Profile photo removed',
            'user'    => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}
