<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NoteApiController extends Controller
{
    /**
     * List notes with pagination (for AJAX infinite scroll)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Note::where('user_id', $user->id)
            ->with(['tags', 'group']);

        // Filter by archive status
        if ($request->boolean('is_archived')) {
            $query->archived();
        } else {
            $query->active();
        }

        // Apply filters
        if ($request->has('filter')) {
            match ($request->filter) {
                'favorites' => $query->favorited(),
                'encrypted' => $query->encrypted(),
                'pinned' => $query->pinned(),
                default => null,
            };
        }

        if ($request->has('tag_id')) {
            $query->withTag($request->tag_id);
        }

        if ($request->has('group_id')) {
            $query->inGroup($request->group_id);
        }

        // Search
        if ($request->filled('q')) {
            $query->where('is_encrypted', false)->search($request->q);
        }

        $notes = $query->orderByPinnedAndDate()
            ->cursorPaginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notes->items(),
            'meta' => [
                'has_more' => $notes->hasMorePages(),
                'next_cursor' => $notes->nextCursor()?->encode(),
                'per_page' => $notes->perPage(),
            ],
        ]);
    }

    /**
     * Get a single note
     */
    public function show(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $note->load(['tags', 'group']);
        $note->recordView();

        return response()->json([
            'success' => true,
            'data' => $note,
        ]);
    }

    /**
     * Search notes
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $query = Note::where('user_id', $user->id)
            ->where('is_encrypted', false)
            ->with(['tags', 'group']);

        if (!$request->boolean('include_archived')) {
            $query->active();
        }

        // Apply search
        if (config('database.default') === 'mysql') {
            $query->fullTextSearch($request->q);
        } else {
            $query->search($request->q);
        }

        // Apply filters
        if ($request->has('group_id')) {
            $query->inGroup($request->group_id);
        }

        if ($request->has('tag_ids')) {
            foreach ($request->tag_ids as $tagId) {
                $query->withTag($tagId);
            }
        }

        $notes = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
            'meta' => [
                'query' => $request->q,
                'total_results' => $notes->count(),
            ],
        ]);
    }

    /**
     * Decrypt a note
     */
    public function decrypt(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        if (!$note->is_encrypted) {
            return response()->json([
                'success' => false,
                'message' => 'This note is not encrypted.',
            ], 400);
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        $encryptedNote = $note->encryptedContent;

        if (!$encryptedNote) {
            return response()->json([
                'success' => false,
                'message' => 'Encrypted content not found.',
            ], 404);
        }

        if ($encryptedNote->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please try again later.',
                'locked_until' => $encryptedNote->locked_until,
            ], 429);
        }

        $decrypted = $encryptedNote->decryptContent($request->code);

        if ($decrypted === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid decryption code.',
                'remaining_attempts' => $encryptedNote->remaining_attempts,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $decrypted,
            ],
        ]);
    }

    /**
     * Toggle pin status
     */
    public function togglePin(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $note->togglePin();

        return response()->json([
            'success' => true,
            'data' => ['is_pinned' => $note->is_pinned],
            'message' => $note->is_pinned ? 'Note pinned.' : 'Note unpinned.',
        ]);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $note->toggleFavorite();

        return response()->json([
            'success' => true,
            'data' => ['is_favorited' => $note->is_favorited],
            'message' => $note->is_favorited ? 'Added to favorites.' : 'Removed from favorites.',
        ]);
    }

    /**
     * Archive/Unarchive note
     */
    public function archive(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $request->validate([
            'archive' => 'required|boolean',
        ]);

        if ($request->archive) {
            $note->archive();
        } else {
            $note->unarchive();
        }

        return response()->json([
            'success' => true,
            'data' => ['is_archived' => $note->is_archived],
            'message' => $note->is_archived ? 'Note archived.' : 'Note unarchived.',
        ]);
    }
}
