<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\NoteShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShareApiController extends Controller
{
    /**
     * Get notes shared with the authenticated user
     */
    public function sharedWithMe(Request $request): JsonResponse
    {
        $shares = NoteShare::where('shared_with_user_id', $request->user()->id)
            ->valid()
            ->with(['note:id,title,slug,excerpt,is_encrypted,updated_at', 'owner:id,name,email'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shares,
        ]);
    }

    /**
     * Get notes shared by the authenticated user
     */
    public function sharedByMe(Request $request): JsonResponse
    {
        $shares = NoteShare::whereHas('note', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->valid()
            ->with(['note:id,title,slug,excerpt,is_encrypted', 'sharedWithUser:id,name,email'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shares,
        ]);
    }

    /**
     * Share a note with a user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'note_id' => 'required|exists:notes,id',
            'email' => 'required|email|exists:users,email',
            'permission' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $note = Note::findOrFail($validated['note_id']);

        // Check ownership
        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only share your own notes.',
            ], 403);
        }

        $sharedWithUser = User::where('email', $validated['email'])->first();

        // Cannot share with yourself
        if ($sharedWithUser->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot share a note with yourself.',
            ], 422);
        }

        // Check if already shared
        $existingShare = NoteShare::where('note_id', $note->id)
            ->where('shared_with_user_id', $sharedWithUser->id)
            ->first();

        if ($existingShare) {
            // Update existing share
            $existingShare->update([
                'permission' => $validated['permission'],
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $existingShare,
                'message' => 'Share updated successfully.',
            ]);
        }

        $share = NoteShare::create([
            'note_id' => $note->id,
            'shared_with_user_id' => $sharedWithUser->id,
            'permission' => $validated['permission'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $share,
            'message' => 'Note shared successfully.',
        ], 201);
    }

    /**
     * Revoke a share
     */
    public function destroy(Request $request, NoteShare $share): JsonResponse
    {
        // Check if user owns the note
        if ($share->note->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $share->delete();

        return response()->json([
            'success' => true,
            'message' => 'Share revoked successfully.',
        ]);
    }
}
