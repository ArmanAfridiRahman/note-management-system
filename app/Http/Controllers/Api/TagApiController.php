<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagApiController extends Controller
{
    /**
     * List all tags for the authenticated user
     * Supports search and cursor-based pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::where('user_id', $request->user()->id)
            ->withCount('notes');

        // Search filter
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // Order by popularity (notes count) or name
        if ($request->get('order') === 'popular') {
            $query->orderByDesc('notes_count')->orderBy('name');
        } else {
            $query->ordered();
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);

        if ($request->has('cursor') || $request->has('page')) {
            $tags = $query->cursorPaginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tags->items(),
                'meta' => [
                    'has_more' => $tags->hasMorePages(),
                    'next_cursor' => $tags->nextCursor()?->encode(),
                    'per_page' => $perPage,
                ],
            ]);
        }

        // Return all tags if no pagination requested
        $tags = $query->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Create a new tag
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ]);

        $tag = Tag::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $tag,
            'message' => 'Tag created successfully.',
        ], 201);
    }

    /**
     * Update a tag
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ]);

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'data' => $tag,
            'message' => 'Tag updated successfully.',
        ]);
    }

    /**
     * Delete a tag
     */
    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}
