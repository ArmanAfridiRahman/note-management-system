<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EncryptedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'unique_code',
        'encrypted_content',
        'encryption_iv',
        'hint',
        'failed_attempts',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EncryptedNote $encryptedNote) {
            if (empty($encryptedNote->unique_code)) {
                $encryptedNote->unique_code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique code for the encrypted note.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (static::where('unique_code', $code)->exists());

        return $code;
    }

    /**
     * Get the parent note.
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * Check if the note is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Get the remaining lockout time in seconds.
     */
    public function getLockoutRemainingSecondsAttribute(): ?int
    {
        if (!$this->isLocked()) {
            return null;
        }

        return $this->locked_until->diffInSeconds(now());
    }

    /**
     * Record a failed decryption attempt.
     */
    public function recordFailedAttempt(): void
    {
        $this->failed_attempts++;

        $maxAttempts = config('theme.notes.max_failed_decrypt_attempts', 5);
        $lockoutMinutes = config('theme.notes.lockout_duration_minutes', 15);

        if ($this->failed_attempts >= $maxAttempts) {
            $this->locked_until = now()->addMinutes($lockoutMinutes);
        }

        $this->save();
    }

    /**
     * Reset failed attempts after successful decryption.
     */
    public function resetFailedAttempts(): void
    {
        $this->failed_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Get remaining attempts before lockout.
     */
    public function getRemainingAttemptsAttribute(): int
    {
        $maxAttempts = config('theme.notes.max_failed_decrypt_attempts', 5);
        return max(0, $maxAttempts - $this->failed_attempts);
    }

    /**
     * Encrypt content using AES-256-CBC.
     */
    public static function encryptContent(string $content, string $password): array
    {
        $algorithm = config('theme.notes.encryption_algorithm', 'AES-256-CBC');
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algorithm));

        $encrypted = openssl_encrypt($content, $algorithm, $key, 0, $iv);

        return [
            'encrypted_content' => base64_encode($encrypted),
            'encryption_iv' => base64_encode($iv),
        ];
    }

    /**
     * Decrypt content using AES-256-CBC.
     */
    public function decryptContent(string $password): ?string
    {
        if ($this->isLocked()) {
            return null;
        }

        $algorithm = config('theme.notes.encryption_algorithm', 'AES-256-CBC');
        $key = hash('sha256', $password, true);
        $iv = base64_decode($this->encryption_iv);
        $encrypted = base64_decode($this->encrypted_content);

        $decrypted = openssl_decrypt($encrypted, $algorithm, $key, 0, $iv);

        if ($decrypted === false) {
            $this->recordFailedAttempt();
            return null;
        }

        $this->resetFailedAttempts();
        return $decrypted;
    }

    /**
     * Verify if a code matches.
     */
    public function verifyCode(string $code): bool
    {
        return strtoupper($code) === strtoupper($this->unique_code);
    }
}
