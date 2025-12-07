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

        // Note state statistics for the chart
        $noteStates = [
            'total' => Note::where('user_id', $user->id)->count(),
            'pinned' => Note::where('user_id', $user->id)->active()->where('is_pinned', true)->count(),
            'favorites' => Note::where('user_id', $user->id)->active()->where('is_favorited', true)->count(),
            'encrypted' => Note::where('user_id', $user->id)->active()->where('is_encrypted', true)->count(),
            'archived' => Note::where('user_id', $user->id)->where('is_archived', true)->count(),
            'regular' => Note::where('user_id', $user->id)
                ->active()
                ->where('is_pinned', false)
                ->where('is_favorited', false)
                ->where('is_encrypted', false)
                ->count(),
            // Overlapping states
            'pinned_favorite' => Note::where('user_id', $user->id)
                ->active()
                ->where('is_pinned', true)
                ->where('is_favorited', true)
                ->count(),
            'pinned_encrypted' => Note::where('user_id', $user->id)
                ->active()
                ->where('is_pinned', true)
                ->where('is_encrypted', true)
                ->count(),
            'favorite_encrypted' => Note::where('user_id', $user->id)
                ->active()
                ->where('is_favorited', true)
                ->where('is_encrypted', true)
                ->count(),
            'pinned_favorite_encrypted' => Note::where('user_id', $user->id)
                ->active()
                ->where('is_pinned', true)
                ->where('is_favorited', true)
                ->where('is_encrypted', true)
                ->count(),
        ];

        // Top 5 most opened notes
        $topOpenedNotes = Note::where('user_id', $user->id)
            ->active()
            ->orderBy('open_count', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'open_count', 'color']);

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
