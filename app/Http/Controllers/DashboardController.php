<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if tag filter is applied
        $tagIds = $request->has('tags')
            ? array_filter(explode(',', $request->get('tags')), fn($id) => is_numeric($id))
            : [];

        // Helper function to apply tag filter to query
        $applyTagFilter = function ($query) use ($tagIds) {
            if (!empty($tagIds)) {
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            }
            return $query;
        };

        // Note state statistics for the chart (with tag filter applied)
        $noteStates = [
            'total' => $applyTagFilter(Note::where('user_id', $user->id))->count(),
            'pinned' => $applyTagFilter(Note::where('user_id', $user->id)->active()->where('is_pinned', true))->count(),
            'favorites' => $applyTagFilter(Note::where('user_id', $user->id)->active()->where('is_favorited', true))->count(),
            'encrypted' => $applyTagFilter(Note::where('user_id', $user->id)->active()->where('is_encrypted', true))->count(),
            'archived' => $applyTagFilter(Note::where('user_id', $user->id)->where('is_archived', true))->count(),
            'regular' => $applyTagFilter(
                Note::where('user_id', $user->id)
                    ->active()
                    ->where('is_pinned', false)
                    ->where('is_favorited', false)
                    ->where('is_encrypted', false)
            )->count(),
            // Overlapping states
            'pinned_favorite' => $applyTagFilter(
                Note::where('user_id', $user->id)
                    ->active()
                    ->where('is_pinned', true)
                    ->where('is_favorited', true)
            )->count(),
            'pinned_encrypted' => $applyTagFilter(
                Note::where('user_id', $user->id)
                    ->active()
                    ->where('is_pinned', true)
                    ->where('is_encrypted', true)
            )->count(),
            'favorite_encrypted' => $applyTagFilter(
                Note::where('user_id', $user->id)
                    ->active()
                    ->where('is_favorited', true)
                    ->where('is_encrypted', true)
            )->count(),
            'pinned_favorite_encrypted' => $applyTagFilter(
                Note::where('user_id', $user->id)
                    ->active()
                    ->where('is_pinned', true)
                    ->where('is_favorited', true)
                    ->where('is_encrypted', true)
            )->count(),
        ];

        // Top 5 most opened notes (with tag filter applied)
        $topOpenedNotesQuery = Note::where('user_id', $user->id)
            ->active()
            ->orderBy('open_count', 'desc')
            ->limit(5);
        $topOpenedNotes = $applyTagFilter($topOpenedNotesQuery)->get(['id', 'title', 'open_count', 'color']);

        // Tags and groups for the notes section
        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();

        // Users for sharing (excluding current user)
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard', [
            'noteStates' => $noteStates,
            'topOpenedNotes' => $topOpenedNotes,
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }
}
