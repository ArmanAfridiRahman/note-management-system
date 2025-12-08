<?php

namespace App\Models;

// Uncomment to enable email verification
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Remove "implements MustVerifyEmail" to enable email verification
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    /**
     * Default preferences for new users.
     */
    public static function defaultPreferences(): array
    {
        return [
            'theme' => 'light',
            'notes_per_page' => 20,
            'default_group_id' => null,
            'show_archived' => false,
            'compact_view' => false,
            'auto_save' => true,
        ];
    }

    /**
     * Get a specific preference with fallback to default.
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = $this->preferences ?? [];
        $defaults = self::defaultPreferences();

        return $preferences[$key] ?? $defaults[$key] ?? $default;
    }

    /**
     * Set a specific preference.
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? self::defaultPreferences();
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Get all notes owned by this user.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get all groups owned by this user.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get all tags owned by this user.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get notes shared with this user.
     */
    public function sharedNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'note_shares', 'shared_with_user_id', 'note_id')
            ->withPivot(['permission', 'share_token', 'expires_at', 'accessed_at', 'message', 'shared_by_user_id'])
            ->withTimestamps()
            ->wherePivot('expires_at', '>', now())
            ->orWherePivotNull('expires_at');
    }

    /**
     * Get shares created by this user.
     */
    public function sharesCreated(): HasMany
    {
        return $this->hasMany(NoteShare::class, 'shared_by_user_id');
    }

    /**
     * Get shares received by this user.
     */
    public function sharesReceived(): HasMany
    {
        return $this->hasMany(NoteShare::class, 'shared_with_user_id');
    }
}
