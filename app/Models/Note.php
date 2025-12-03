<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'group_id',
        'parent_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'is_encrypted',
        'is_pinned',
        'is_archived',
        'is_favorited',
        'color',
        'meta_data',
        'archived_at',
        'last_viewed_at',
    ];

    protected $appends = ['encryption_hint'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
            'is_favorited' => 'boolean',
            'archived_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'meta_data' => 'array',
        ];
    }

    /**
     * Get the encryption hint from the encrypted content.
     */
    public function getEncryptionHintAttribute(): ?string
    {
        if (!$this->is_encrypted) {
            return null;
        }

        return $this->encryptedContent?->hint;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Note $note) {
            if (empty($note->slug)) {
                $note->slug = static::generateUniqueSlug($note->title, $note->user_id);
            }

            if (empty($note->excerpt) && !empty($note->content)) {
                $note->excerpt = static::generateExcerpt($note->content);
            }
        });

        static::updating(function (Note $note) {
            if ($note->isDirty('title')) {
                $note->slug = static::generateUniqueSlug($note->title, $note->user_id, $note->id);
            }

            if ($note->isDirty('content') && !empty($note->content)) {
                $note->excerpt = static::generateExcerpt($note->content);
            }
        });
    }

    /**
     * Generate a unique slug for the note.
     */
    public static function generateUniqueSlug(string $title, int $userId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('user_id', $userId)
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate an excerpt from content.
     */
    public static function generateExcerpt(string $content, int $maxLength = 500): string
    {
        // Strip HTML tags
        $text = strip_tags($content);
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return Str::limit($text, $maxLength);
    }

    /**
     * Get the owner of the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group this note belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the parent note (for replicated notes).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    /**
     * Get all notes replicated from this note.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id');
    }

    /**
     * Check if this note is a replica.
     */
    public function isReplica(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the parent note's state at replication time (from meta_data).
     */
    public function getParentSnapshotAttribute(): ?array
    {
        return $this->meta_data['parent_snapshot'] ?? null;
    }

    /**
     * Replicate this note as a new note.
     */
    public function replicateAsChild(): Note
    {
        $replica = $this->replicate(['slug', 'parent_id', 'meta_data']);
        $replica->parent_id = $this->id;
        $replica->group_id = null; // Replicated notes start ungrouped
        $replica->meta_data = [
            'parent_snapshot' => [
                'title' => $this->title,
                'content' => $this->content,
                'excerpt' => $this->excerpt,
                'color' => $this->color,
                'replicated_at' => now()->toISOString(),
            ],
        ];
        $replica->save();

        return $replica;
    }

    /**
     * Assign this note to a group.
     */
    public function assignToGroup(?Group $group): void
    {
        $this->group_id = $group?->id;
        $this->save();
    }

    /**
     * Remove this note from its group.
     */
    public function removeFromGroup(): void
    {
        $this->group_id = null;
        $this->save();
    }

    /**
     * Get the encrypted content for this note.
     */
    public function encryptedContent(): HasOne
    {
        return $this->hasOne(EncryptedNote::class);
    }

    /**
     * Get all tags for this note.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'note_tag')
            ->withPivot('created_at');
    }

    /**
     * Get all shares for this note.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(NoteShare::class);
    }

    /**
     * Get users this note is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'note_shares', 'note_id', 'shared_with_user_id')
            ->withPivot(['permission', 'share_token', 'expires_at', 'accessed_at', 'message'])
            ->withTimestamps();
    }

    /**
     * Scope: Only non-archived notes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Only archived notes.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Only pinned notes.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: Only favorited notes.
     */
    public function scopeFavorited($query)
    {
        return $query->where('is_favorited', true);
    }

    /**
     * Scope: Only encrypted notes.
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope: Search by title and content.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    /**
     * Scope: Full-text search (MySQL).
     */
    public function scopeFullTextSearch($query, string $term)
    {
        if (config('database.default') === 'mysql') {
            return $query->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$term]);
        }

        return $this->scopeSearch($query, $term);
    }

    /**
     * Scope: Filter by tag.
     */
    public function scopeWithTag($query, int $tagId)
    {
        return $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
    }

    /**
     * Scope: Filter by group.
     */
    public function scopeInGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope: Order by pinned first, then by date.
     */
    public function scopeOrderByPinnedAndDate($query, string $direction = 'desc')
    {
        return $query->orderBy('is_pinned', 'desc')
                     ->orderBy('created_at', $direction);
    }

    /**
     * Archive the note.
     */
    public function archive(): bool
    {
        $this->is_archived = true;
        $this->archived_at = now();
        return $this->save();
    }

    /**
     * Unarchive the note.
     */
    public function unarchive(): bool
    {
        $this->is_archived = false;
        $this->archived_at = null;
        return $this->save();
    }

    /**
     * Toggle pin status.
     */
    public function togglePin(): bool
    {
        $this->is_pinned = !$this->is_pinned;
        return $this->save();
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(): bool
    {
        $this->is_favorited = !$this->is_favorited;
        return $this->save();
    }

    /**
     * Record that the note was viewed.
     */
    public function recordView(): void
    {
        $this->last_viewed_at = now();
        $this->saveQuietly();
    }
}
