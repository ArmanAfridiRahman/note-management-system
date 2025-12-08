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
     * Get notes shared with the authenticated user (legacy format with share details)
     */
    public function sharedWithMe(Request $request): JsonResponse
    {
        $shares = NoteShare::where('shared_with_user_id', $request->user()->id)
            ->valid()
            ->with([
                'note:id,title,slug,excerpt,is_encrypted,updated_at,user_id',
                'note.user:id,name,email',
                'sharedByUser:id,name,email'
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shares,
        ]);
    }

    /**
     * Get notes shared with the authenticated user (NoteGrid compatible format)
     */
    public function sharedWithMeNotes(Request $request): JsonResponse
    {
        $shares = NoteShare::where('shared_with_user_id', $request->user()->id)
            ->valid()
            ->with([
                'note' => function ($query) {
                    $query->with(['tags', 'user:id,name,email']);
                },
                'sharedByUser:id,name,email'
            ])
            ->latest()
            ->get();

        // Transform to DisplayItem format with share metadata attached to notes
        $displayItems = $shares->map(function ($share) {
            $note = $share->note;
            if (!$note) return null;

            // Add share metadata to the note
            $noteData = $note->toArray();
            $noteData['share'] = [
                'id' => $share->id,
                'permission' => $share->permission,
                'expires_at' => $share->expires_at,
                'message' => $share->message,
                'shared_by' => $share->sharedByUser,
                'created_at' => $share->created_at,
            ];
            $noteData['is_shared_with_me'] = true;

            return [
                'type' => 'note',
                'note' => $noteData,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $displayItems,
            'meta' => [
                'has_more' => false,
                'next_cursor' => null,
                'per_page' => 50,
            ],
        ]);
    }

    /**
     * Get notes shared by the authenticated user (legacy format with share details)
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
     * Get notes shared by the authenticated user (NoteGrid compatible format)
     */
    public function sharedByMeNotes(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get all notes that have active shares
        $notesWithShares = Note::where('user_id', $userId)
            ->whereHas('shares', function ($query) {
                $query->valid();
            })
            ->with(['tags', 'shares' => function ($query) {
                $query->valid()->with('sharedWithUser:id,name,email');
            }])
            ->latest()
            ->get();

        // Transform to DisplayItem format with share metadata
        $displayItems = $notesWithShares->map(function ($note) {
            $noteData = $note->toArray();
            $noteData['share_recipients'] = $note->shares->map(function ($share) {
                return [
                    'id' => $share->id,
                    'user' => $share->sharedWithUser,
                    'permission' => $share->permission,
                    'expires_at' => $share->expires_at,
                    'message' => $share->message,
                    'created_at' => $share->created_at,
                ];
            });
            $noteData['is_shared_by_me'] = true;
            unset($noteData['shares']); // Remove raw shares array

            return [
                'type' => 'note',
                'note' => $noteData,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $displayItems,
            'meta' => [
                'has_more' => false,
                'next_cursor' => null,
                'per_page' => 50,
            ],
        ]);
    }

    /**
     * Get existing shares for a specific note
     */
    public function getNoteShares(Request $request, Note $note): JsonResponse
    {
        // Check if user owns the note
        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $shares = NoteShare::where('note_id', $note->id)
            ->valid()
            ->with('sharedWithUser:id,name,email')
            ->latest()
            ->get()
            ->map(function ($share) {
                return [
                    'id' => $share->id,
                    'user' => $share->sharedWithUser,
                    'permission' => $share->permission,
                    'expires_at' => $share->expires_at,
                    'message' => $share->message,
                    'created_at' => $share->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $shares,
        ]);
    }

    /**
     * Share a note with one or multiple users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'note_id' => 'required|exists:notes,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'permission' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
            'message' => 'nullable|string|max:500',
        ]);

        $note = Note::findOrFail($validated['note_id']);
        $currentUserId = $request->user()->id;

        // Check ownership
        if ($note->user_id !== $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You can only share your own notes.',
            ], 403);
        }

        $shares = [];
        $skipped = [];

        foreach ($validated['user_ids'] as $userId) {
            // Cannot share with yourself
            if ($userId == $currentUserId) {
                $skipped[] = $userId;
                continue;
            }

            // Check if already shared
            $existingShare = NoteShare::where('note_id', $note->id)
                ->where('shared_with_user_id', $userId)
                ->first();

            if ($existingShare) {
                // Update existing share
                $existingShare->update([
                    'permission' => $validated['permission'],
                    'expires_at' => $validated['expires_at'] ?? null,
                    'message' => $validated['message'] ?? null,
                ]);
                $shares[] = $existingShare;
            } else {
                // Create new share
                $share = NoteShare::create([
                    'note_id' => $note->id,
                    'shared_by_user_id' => $currentUserId,
                    'shared_with_user_id' => $userId,
                    'permission' => $validated['permission'],
                    'expires_at' => $validated['expires_at'] ?? null,
                    'message' => $validated['message'] ?? null,
                ]);
                $shares[] = $share;
            }
        }

        $count = count($shares);
        $message = $count === 1
            ? 'Note shared successfully.'
            : "Note shared with {$count} users.";

        return response()->json([
            'success' => true,
            'data' => $shares,
            'message' => $message,
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
