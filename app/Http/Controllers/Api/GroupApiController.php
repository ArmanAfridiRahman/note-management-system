<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroupApiController extends Controller
{
    /**
     * List all groups for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::where('user_id', $request->user()->id)
            ->root()
            ->with(['children' => function ($query) {
                $query->withCount('notes')->ordered();
            }])
            ->withCount('notes')
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Create a new group
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:groups,id',
        ]);

        // Verify parent belongs to user
        if (!empty($validated['parent_id'])) {
            $parent = Group::find($validated['parent_id']);
            if ($parent->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent group.',
                ], 422);
            }
        }

        $group = Group::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $group,
            'message' => 'Group created successfully.',
        ], 201);
    }

    /**
     * Update a group
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|nullable|string|max:7',
            'parent_id' => 'sometimes|nullable|exists:groups,id',
        ]);

        // Prevent setting itself or its children as parent
        if (!empty($validated['parent_id'])) {
            if ($validated['parent_id'] == $group->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A group cannot be its own parent.',
                ], 422);
            }

            $parent = Group::find($validated['parent_id']);
            if ($parent->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent group.',
                ], 422);
            }
        }

        // Only update fields that were provided
        $updateData = [];
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('color', $validated)) {
            $updateData['color'] = $validated['color'];
        }
        if (array_key_exists('parent_id', $validated)) {
            $updateData['parent_id'] = $validated['parent_id'];
        }

        $group->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $group,
            'message' => 'Group updated successfully.',
        ]);
    }

    /**
     * Delete a group
     */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Move notes to root level
        $group->notes()->update(['group_id' => null]);

        // Move children to root level
        $group->children()->update(['parent_id' => null]);

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully.',
        ]);
    }

    /**
     * Merge one group into another
     * All notes from source group are moved to target group, then source is deleted
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_group_id' => 'required|exists:groups,id',
            'target_group_id' => 'required|exists:groups,id|different:source_group_id',
        ]);

        $sourceGroup = Group::find($validated['source_group_id']);
        $targetGroup = Group::find($validated['target_group_id']);

        // Verify both groups belong to the current user
        if ($sourceGroup->user_id !== $request->user()->id || $targetGroup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Move all notes from source group to target group
        $sourceGroup->notes()->update(['group_id' => $targetGroup->id]);

        // Move any child groups to target group
        $sourceGroup->children()->update(['parent_id' => $targetGroup->id]);

        // Delete the source group
        $sourceGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Groups merged successfully.',
            'data' => $targetGroup->load('notes'),
        ]);
    }
}
