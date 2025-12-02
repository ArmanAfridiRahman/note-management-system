<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NoteShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'share_token',
        'permission',
        'expires_at',
        'accessed_at',
        'access_count',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accessed_at' => 'datetime',
            'access_count' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NoteShare $share) {
            if (empty($share->share_token)) {
                $share->share_token = static::generateToken();
            }
        });
    }

    /**
     * Generate a unique share token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Get the shared note.
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * Get the user who shared the note.
     */
    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    /**
     * Get the user the note is shared with.
     */
    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    /**
     * Check if the share is still valid.
     */
    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the share has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the share has edit permission.
     */
    public function canEdit(): bool
    {
        return $this->permission === 'edit' && $this->isValid();
    }

    /**
     * Record an access to the shared note.
     */
    public function recordAccess(): void
    {
        $this->access_count++;
        $this->accessed_at = now();
        $this->save();
    }

    /**
     * Scope: Only valid (non-expired) shares.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Only expired shares.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Shares received by a user.
     */
    public function scopeReceivedBy($query, int $userId)
    {
        return $query->where('shared_with_user_id', $userId);
    }

    /**
     * Scope: Shares created by a user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('shared_by_user_id', $userId);
    }

    /**
     * Find a share by its token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('share_token', $token)->first();
    }
}
