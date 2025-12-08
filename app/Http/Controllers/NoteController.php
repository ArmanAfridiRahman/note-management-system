<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Group;
use App\Models\User;
use App\Models\EncryptedNote;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
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

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Notes/Archived', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }

    public function favorites(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Notes/Favorites', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }

    public function encrypted(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Notes/Encrypted', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
        ]);
    }

    public function pinned(Request $request): Response
    {
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)->ordered()->get();
        $groups = Group::where('user_id', $user->id)->root()->ordered()->get();
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Notes/Pinned', [
            'tags' => $tags,
            'groups' => $groups,
            'users' => $users,
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
            'encryption_password' => [
                'nullable',
                'string',
                'min:4',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->boolean('is_encrypted') && empty($value)) {
                        $fail('The encryption password is required when encryption is enabled.');
                    }
                },
            ],
            'encryption_hint' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'is_pinned' => 'boolean',
            'is_favorited' => 'boolean',
            'is_archived' => 'boolean',
        ]);

        $user = $request->user();

        // Prepare content and excerpt
        $content = Arr::get($validated, 'content');
        $excerpt = null;

        // Generate excerpt from plain content first (before encryption)
        if (!empty($content)) {
            $excerpt = Note::generateExcerpt($content);
        }

        // Handle encryption - encrypt content and excerpt before storing
        $isEncrypted = $validated['is_encrypted'] ?? false;
        if ($isEncrypted && !empty($content)) {
            // Encrypt the content and excerpt
            $encryptedData = EncryptedNote::encryptNoteData(
                $content,
                $excerpt,
                $validated['encryption_password']
            );

            // Store encrypted versions in the note
            $content = $encryptedData['encrypted_content'];
            $excerpt = $encryptedData['encrypted_excerpt'];
        }

        // Create the note with encrypted or plain content
        $note = Note::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'content' => $content,
            'excerpt' => $excerpt,
            'group_id' => $validated['group_id'] ?? null,
            'is_encrypted' => $isEncrypted,
            'color' => $validated['color'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_favorited' => $validated['is_favorited'] ?? false,
            'is_archived' => $validated['is_archived'] ?? false,
        ]);

        // Create encrypted note record with hashed code and IV
        if ($isEncrypted && !empty($validated['content'])) {
            EncryptedNote::create([
                'note_id' => $note->id,
                'encryption_code_hash' => EncryptedNote::hashCode($validated['encryption_password']),
                'encrypted_content' => '', // Content stored in notes table now
                'encryption_iv' => $encryptedData['encryption_iv'],
                'hint' => $validated['encryption_hint'] ?? null,
            ]);
        }

        // Attach tags
        if (!empty($validated['tag_ids'])) {
            $note->tags()->attach($validated['tag_ids']);
        }

        // Return JSON for non-Inertia AJAX requests (API calls)
        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
            $note->load(['tags', 'group']);
            return response()->json($note);
        }

        // For Inertia and regular requests, return redirect
        return redirect()->route('notes.index')
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
            'is_encrypted' => 'boolean',
            'encryption_password' => [
                'nullable',
                'string',
                'min:4',
                function ($attribute, $value, $fail) use ($request, $note) {
                    // Only require password if enabling encryption on a non-encrypted note
                    if ($request->boolean('is_encrypted') && !$note->is_encrypted && empty($value)) {
                        $fail('The encryption password is required when enabling encryption.');
                    }
                },
            ],
            'encryption_hint' => 'nullable|string|max:255',
        ]);

        // Handle enabling encryption on existing note
        $isEnablingEncryption = Arr::get($validated, 'is_encrypted') && !$note->is_encrypted;

        $content = $note->is_encrypted ? $note->content : ($validated['content'] ?? '');
        $excerpt = $note->is_encrypted ? $note->excerpt : null;

        if ($isEnablingEncryption && !empty($validated['content'])) {
            // Generate excerpt from plain content before encryption
            $excerpt = Note::generateExcerpt($validated['content']);

            // Encrypt the content and excerpt
            $encryptedData = EncryptedNote::encryptNoteData(
                $validated['content'],
                $excerpt,
                $validated['encryption_password']
            );

            // Store encrypted versions
            $content = $encryptedData['encrypted_content'];
            $excerpt = $encryptedData['encrypted_excerpt'];
        }

        $note->update([
            'title' => $validated['title'],
            'content' => $content,
            'excerpt' => $excerpt,
            'group_id' => $validated['group_id'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_encrypted' => $isEnablingEncryption ? true : $note->is_encrypted,
        ]);

        // Create encrypted note record if enabling encryption
        if ($isEnablingEncryption && !empty($validated['content'])) {
            EncryptedNote::create([
                'note_id' => $note->id,
                'encryption_code_hash' => EncryptedNote::hashCode($validated['encryption_password']),
                'encrypted_content' => '', // Content stored in notes table now
                'encryption_iv' => $encryptedData['encryption_iv'],
                'hint' => $validated['encryption_hint'] ?? null,
            ]);
        }

        // Sync tags
        $note->tags()->sync($validated['tag_ids'] ?? []);

        // Return JSON for non-Inertia AJAX requests (API calls)
        if (($request->wantsJson() || $request->ajax()) && !$request->header('X-Inertia')) {
            $note->load(['tags', 'group']);
            return response()->json($note);
        }

        // For Inertia and regular requests, return redirect
        return redirect()->route('notes.index')
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
