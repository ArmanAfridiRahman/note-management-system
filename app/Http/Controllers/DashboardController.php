<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Recent notes
        $recentNotes = Note::where('user_id', $user->id)
            ->with(['tags', 'group'])
            ->active()
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Pinned notes
        $pinnedNotes = Note::where('user_id', $user->id)
            ->with(['tags', 'group'])
            ->active()
            ->pinned()
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Stats
        $stats = [
            'total_notes' => Note::where('user_id', $user->id)->active()->count(),
            'archived_notes' => Note::where('user_id', $user->id)->archived()->count(),
            'encrypted_notes' => Note::where('user_id', $user->id)->encrypted()->count(),
            'total_tags' => Tag::where('user_id', $user->id)->count(),
            'total_groups' => Group::where('user_id', $user->id)->count(),
        ];

        // Popular tags
        $popularTags = Tag::where('user_id', $user->id)
            ->withCount('notes')
            ->orderByDesc('notes_count')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'recentNotes' => $recentNotes,
            'pinnedNotes' => $pinnedNotes,
            'stats' => $stats,
            'popularTags' => $popularTags,
        ]);
    }
}
