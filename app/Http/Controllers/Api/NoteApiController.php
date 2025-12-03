<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NoteApiController extends Controller
{
    /**
     * List notes with pagination (for AJAX infinite scroll)
     * Returns ungrouped notes and groups with their notes
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Determine archive status
        $isArchived = $request->boolean('is_archived');

        // Build a closure to apply common filters to notes
        $applyFilters = function ($query) use ($request, $isArchived) {
            // Filter by archive status
            if ($isArchived) {
                $query->archived();
            } else {
                $query->active();
            }

            // Apply type filters
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

            // Search
            if ($request->filled('q')) {
                $query->where('is_encrypted', false)->search($request->q);
            }

            return $query;
        };

        // Get all groups with their notes matching the filters
        $groups = Group::where('user_id', $user->id)
            ->with(['notes' => function ($query) use ($applyFilters) {
                $applyFilters($query)->with('tags');
            }])
            ->get()
            ->filter(fn ($group) => $group->notes->count() > 0); // Only groups with matching notes

        // Build query for truly ungrouped notes only (group_id IS NULL)
        $query = Note::where('user_id', $user->id)
            ->whereNull('group_id')
            ->with(['tags']);

        // Apply the same filters to ungrouped notes
        $applyFilters($query);

        if ($request->has('group_id')) {
            $query->inGroup($request->group_id);
        }

        $notes = $query->orderByPinnedAndDate()->get();

        // Combine ungrouped notes and groups into a single display list
        $displayItems = collect();

        // Add ungrouped notes
        foreach ($notes as $note) {
            $displayItems->push([
                'type' => 'note',
                'note' => $note,
            ]);
        }

        // Add groups (sorted so groups with pinned notes come first)
        $sortedGroups = $groups->sortByDesc(function ($group) {
            return $group->notes->contains(fn ($note) => $note->is_pinned);
        });

        foreach ($sortedGroups as $group) {
            // Extract notes separately to avoid duplication in JSON
            $groupNotes = $group->notes;
            $groupData = $group->toArray();
            unset($groupData['notes']); // Remove nested notes from group

            $displayItems->push([
                'type' => 'group',
                'group' => $groupData,
                'notes' => $groupNotes,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $displayItems->values(),
            'meta' => [
                'has_more' => false,
                'next_cursor' => null,
                'per_page' => 50,
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
     * Update note color
     */
    public function updateColor(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'color' => 'nullable|string|max:7',
        ]);

        $note->update(['color' => $validated['color'] ?? null]);

        return response()->json([
            'success' => true,
            'data' => ['color' => $note->color],
            'message' => 'Color updated.',
        ]);
    }

    /**
     * Update note tags
     */
    public function updateTags(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $note->tags()->sync($validated['tag_ids'] ?? []);
        $note->load('tags');

        return response()->json([
            'success' => true,
            'data' => ['tags' => $note->tags],
            'message' => 'Tags updated.',
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

    /**
     * Add note to a group (for drag-drop functionality)
     */
    public function addToGroup(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = Group::findOrFail($request->group_id);

        // Ensure the group belongs to the same user
        if ($group->user_id !== $note->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Group does not belong to the user.',
            ], 403);
        }

        // Remove from any existing group first (a note can only be in one group)
        $note->update(['group_id' => $group->id]);

        $note->load(['tags', 'group']);

        return response()->json([
            'success' => true,
            'data' => $note,
            'message' => "Note added to '{$group->name}'.",
        ]);
    }

    /**
     * Remove note from its group
     */
    public function removeFromGroup(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $groupName = $note->group?->name ?? 'group';

        $note->update(['group_id' => null]);

        $note->load(['tags', 'group']);

        return response()->json([
            'success' => true,
            'data' => $note,
            'message' => "Note removed from '{$groupName}'.",
        ]);
    }

    /**
     * Replicate a note (create a child copy with parent snapshot)
     */
    public function replicate(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $replica = $note->replicateAsChild();
        $replica->load(['tags', 'group', 'parent']);

        return response()->json([
            'success' => true,
            'data' => $replica,
            'message' => "Note replicated successfully.",
        ]);
    }

    /**
     * Create a group from two notes (drag note onto another note)
     */
    public function createGroupFromNotes(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'source_note_id' => 'required|exists:notes,id',
            'target_note_id' => 'required|exists:notes,id|different:source_note_id',
            'group_name' => 'nullable|string|max:255',
        ]);

        $sourceNote = Note::where('user_id', $user->id)->findOrFail($request->source_note_id);
        $targetNote = Note::where('user_id', $user->id)->findOrFail($request->target_note_id);

        // Remove notes from any existing groups (a note can only be in one group)
        $oldSourceGroupId = $sourceNote->group_id;
        $oldTargetGroupId = $targetNote->group_id;

        // Create a new group with combined name or custom name
        $groupName = $request->group_name ?? $targetNote->title . ' + ' . $sourceNote->title;

        $group = Group::create([
            'user_id' => $user->id,
            'name' => $groupName,
            'description' => 'Created by grouping notes',
        ]);

        // Add both notes to the new group
        $targetNote->update(['group_id' => $group->id]);
        $sourceNote->update(['group_id' => $group->id]);

        // Delete any empty groups that result from this
        if ($oldSourceGroupId) {
            $oldGroup = Group::find($oldSourceGroupId);
            if ($oldGroup && $oldGroup->notes()->count() === 0) {
                $oldGroup->delete();
            }
        }
        if ($oldTargetGroupId && $oldTargetGroupId !== $oldSourceGroupId) {
            $oldGroup = Group::find($oldTargetGroupId);
            if ($oldGroup && $oldGroup->notes()->count() === 0) {
                $oldGroup->delete();
            }
        }

        $group->load('notes');

        return response()->json([
            'success' => true,
            'data' => $group,
            'message' => "Group '{$group->name}' created with 2 notes.",
        ]);
    }
}
