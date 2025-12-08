<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UserApiController extends Controller
{
    /**
     * Get user preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $user->preferences ?? $user::defaultPreferences(),
        ]);
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes_per_page' => 'sometimes|integer|min:5|max:100',
            'auto_save' => 'sometimes|boolean',
            'theme' => 'sometimes|string|in:light,dark',
            'compact_view' => 'sometimes|boolean',
            'show_archived' => 'sometimes|boolean',
            'default_group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ]);

        $user = $request->user();
        $preferences = $user->preferences ?? $user::defaultPreferences();

        foreach ($validated as $key => $value) {
            $preferences[$key] = $value;
        }

        $user->preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user->preferences,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            $oldPath = public_path(ltrim($user->avatar, '/'));
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Ensure avatars directory exists
        $avatarsDir = public_path('avatars');
        if (!is_dir($avatarsDir)) {
            mkdir($avatarsDir, 0755, true);
        }

        // Store new avatar in public/avatars
        $file = $request->file('avatar');
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($avatarsDir, $filename);

        $user->avatar = '/avatars/' . $filename;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
            ],
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            $path = public_path(ltrim($user->avatar, '/'));
            if (file_exists($path)) {
                unlink($path);
            }
            $user->avatar = null;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully',
        ]);
    }

    /**
     * Update user colors.
     */
    public function updateColors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $user = $request->user();
        $user->color = $validated['color'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'color' => $user->color,
                'display_color' => $user->display_color,
            ],
            'message' => 'Colors updated successfully',
        ]);
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }
}
