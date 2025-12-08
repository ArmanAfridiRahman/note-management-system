<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedController extends Controller
{
    /**
     * Display notes shared with the authenticated user.
     */
    public function withMe(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Shared/WithMe', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }

    /**
     * Display notes shared by the authenticated user.
     */
    public function byMe(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Shared/ByMe', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }
}
