<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sort_order',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $group->slug = static::generateUniqueSlug($group->name, $group->user_id);
            }
        });

        static::updating(function (Group $group) {
            if ($group->isDirty('name')) {
                $group->slug = static::generateUniqueSlug($group->name, $group->user_id, $group->id);
            }
        });
    }

    /**
     * Generate a unique slug for the group.
     */
    public static function generateUniqueSlug(string $name, int $userId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
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
     * Get the owner of the group.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent group.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    /**
     * Get child groups.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    /**
     * Get all descendant groups recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all notes in this group.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get notes count including children groups.
     */
    public function getTotalNotesCountAttribute(): int
    {
        $count = $this->notes()->count();

        foreach ($this->children as $child) {
            $count += $child->total_notes_count;
        }

        return $count;
    }

    /**
     * Get all ancestor groups.
     */
    public function getAncestorsAttribute(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($ancestors, $parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get the full path of the group (e.g., "Work / Projects / Active").
     */
    public function getFullPathAttribute(): string
    {
        $path = collect($this->ancestors)->pluck('name')->push($this->name);
        return $path->implode(' / ');
    }

    /**
     * Scope: Only root groups (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this group is an ancestor of another group.
     */
    public function isAncestorOf(Group $group): bool
    {
        $parent = $group->parent;

        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Move this group to be a child of another group.
     */
    public function moveTo(?Group $newParent): bool
    {
        // Prevent moving to self or descendant
        if ($newParent && ($newParent->id === $this->id || $this->isAncestorOf($newParent))) {
            return false;
        }

        $this->parent_id = $newParent?->id;
        return $this->save();
    }
}
