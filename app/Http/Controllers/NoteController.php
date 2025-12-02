<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Group;
use App\Models\User;
use App\Models\EncryptedNote;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Note::where('user_id', $user->id)
            ->with(['tags', 'group'])
            ->active();

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

        $notes = $query->orderByPinnedAndDate()
            ->cursorPaginate($request->per_page ?? 20);

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();

        // Get users for sharing (excluding current user)
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
            'filters' => $request->only(['filter', 'tag_id', 'group_id']),
        ]);
    }

    public function archived(Request $request): Response
    {
        $user = $request->user();

        $notes = Note::where('user_id', $user->id)
            ->with(['tags', 'group'])
            ->archived()
            ->orderBy('archived_at', 'desc')
            ->cursorPaginate($request->per_page ?? 20);

        return Inertia::render('Notes/Archived', [
            'notes' => $notes,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->with('children')->ordered()->get();

        return Inertia::render('Notes/Create', [
            'tags' => $tags,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'group_id' => 'nullable|exists:groups,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'is_encrypted' => 'boolean',
            'encryption_password' => 'required_if:is_encrypted,true|nullable|string|min:4',
            'encryption_hint' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'is_pinned' => 'boolean',
        ]);

        $user = $request->user();

        // Create the note
        $note = Note::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['is_encrypted'] ? null : ($validated['content'] ?? ''),
            'group_id' => $validated['group_id'] ?? null,
            'is_encrypted' => $validated['is_encrypted'] ?? false,
            'color' => $validated['color'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        // Handle encryption
        if ($validated['is_encrypted'] && !empty($validated['content'])) {
            $encryptedData = EncryptedNote::encryptContent(
                $validated['content'],
                $validated['encryption_password']
            );

            EncryptedNote::create([
                'note_id' => $note->id,
                'encrypted_content' => $encryptedData['encrypted_content'],
                'encryption_iv' => $encryptedData['encryption_iv'],
                'hint' => $validated['encryption_hint'] ?? null,
            ]);
        }

        // Attach tags
        if (!empty($validated['tag_ids'])) {
            $note->tags()->attach($validated['tag_ids']);
        }

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            $note->load(['tags', 'group']);
            return response()->json($note);
        }

        return redirect()->route('notes.show', $note)
            ->with('success', 'Note created successfully.');
    }

    public function show(Request $request, Note $note): Response
    {
        $this->authorize('view', $note);

        $note->load(['tags', 'group', 'encryptedContent', 'shares.sharedWithUser']);
        $note->recordView();

        return Inertia::render('Notes/Show', [
            'note' => $note,
            'isEncrypted' => $note->is_encrypted,
            'uniqueCode' => $note->encryptedContent?->unique_code,
        ]);
    }

    public function edit(Request $request, Note $note): Response
    {
        $this->authorize('update', $note);

        $user = $request->user();
        $note->load(['tags', 'group']);

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->with('children')->ordered()->get();

        return Inertia::render('Notes/Edit', [
            'note' => $note,
            'tags' => $tags,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, Note $note): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'group_id' => 'nullable|exists:groups,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'color' => 'nullable|string|max:7',
            'is_pinned' => 'boolean',
        ]);

        $note->update([
            'title' => $validated['title'],
            'content' => $note->is_encrypted ? $note->content : ($validated['content'] ?? ''),
            'group_id' => $validated['group_id'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        // Sync tags
        $note->tags()->sync($validated['tag_ids'] ?? []);

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            $note->load(['tags', 'group']);
            return response()->json($note);
        }

        return redirect()->route('notes.show', $note)
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->route('notes.index')
            ->with('success', 'Note moved to trash.');
    }

    public function archive(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        if ($note->is_archived) {
            $note->unarchive();
            $message = 'Note unarchived.';
        } else {
            $note->archive();
            $message = 'Note archived.';
        }

        return back()->with('success', $message);
    }

    public function togglePin(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->togglePin();

        return back()->with('success', $note->is_pinned ? 'Note pinned.' : 'Note unpinned.');
    }

    public function toggleFavorite(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->toggleFavorite();

        return back()->with('success', $note->is_favorited ? 'Added to favorites.' : 'Removed from favorites.');
    }
}
