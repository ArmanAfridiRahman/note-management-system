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

        // Stats
        $stats = [
            'total_notes' => Note::where('user_id', $user->id)->active()->count(),
            'archived_notes' => Note::where('user_id', $user->id)->archived()->count(),
            'encrypted_notes' => Note::where('user_id', $user->id)->active()->encrypted()->count(),
            'favorited_notes' => Note::where('user_id', $user->id)->active()->favorited()->count(),
            'total_tags' => Tag::where('user_id', $user->id)->count(),
            'total_groups' => Group::where('user_id', $user->id)->count(),
        ];

        // Popular tags
        $popularTags = Tag::where('user_id', $user->id)
            ->withCount('notes')
            ->orderByDesc('notes_count')
            ->limit(10)
            ->get();

        // Tags and groups for the notes section
        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();

        // Users for sharing (excluding current user)
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'popularTags' => $popularTags,
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }
}
