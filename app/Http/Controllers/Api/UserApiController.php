<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
