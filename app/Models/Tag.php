<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateUniqueSlug($tag->name, $tag->user_id);
            }
        });

        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = static::generateUniqueSlug($tag->name, $tag->user_id, $tag->id);
            }
        });
    }

    /**
     * Generate a unique slug for the tag.
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
     * Get the owner of the tag.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all notes with this tag.
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'note_tag')
            ->withTimestamps();
    }

    /**
     * Scope: Order by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope: Order by usage count.
     */
    public function scopePopular($query)
    {
        return $query->withCount('notes')->orderByDesc('notes_count');
    }
}
