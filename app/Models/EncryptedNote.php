<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class EncryptedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'encryption_code_hash',
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
     * Hash an encryption code using bcrypt.
     */
    public static function hashCode(string $code): string
    {
        return Hash::make($code);
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
     * Encrypt content and excerpt using AES-256-CBC.
     * Returns encrypted data to be stored in the notes table.
     */
    public static function encryptNoteData(string $content, ?string $excerpt, string $password): array
    {
        $algorithm = config('theme.notes.encryption_algorithm', 'AES-256-CBC');
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algorithm));

        $encryptedContent = openssl_encrypt($content, $algorithm, $key, 0, $iv);
        $encryptedExcerpt = $excerpt ? openssl_encrypt($excerpt, $algorithm, $key, 0, $iv) : null;

        return [
            'encrypted_content' => base64_encode($encryptedContent),
            'encrypted_excerpt' => $encryptedExcerpt ? base64_encode($encryptedExcerpt) : null,
            'encryption_iv' => base64_encode($iv),
        ];
    }

    /**
     * Encrypt content using AES-256-CBC (legacy support).
     */
    public static function encryptContent(string $content, string $password): array
    {
        $result = static::encryptNoteData($content, null, $password);
        return [
            'encrypted_content' => $result['encrypted_content'],
            'encryption_iv' => $result['encryption_iv'],
        ];
    }

    /**
     * Decrypt a string using AES-256-CBC with stored IV.
     */
    public static function decryptString(string $encryptedData, string $password, string $iv): ?string
    {
        $algorithm = config('theme.notes.encryption_algorithm', 'AES-256-CBC');
        $key = hash('sha256', $password, true);
        $ivDecoded = base64_decode($iv);
        $encrypted = base64_decode($encryptedData);

        $decrypted = openssl_decrypt($encrypted, $algorithm, $key, 0, $ivDecoded);

        return $decrypted !== false ? $decrypted : null;
    }

    /**
     * Decrypt content using AES-256-CBC with code verification.
     */
    public function decryptContent(string $code): ?string
    {
        if ($this->isLocked()) {
            return null;
        }

        // First verify the code against the hash
        if (!$this->verifyCode($code)) {
            $this->recordFailedAttempt();
            return null;
        }

        // Get the note's encrypted content from the parent note
        $note = $this->note;
        if (!$note || !$note->content) {
            return null;
        }

        // Decrypt the content stored in notes.content
        $decrypted = static::decryptString($note->content, $code, $this->encryption_iv);

        if ($decrypted === null) {
            $this->recordFailedAttempt();
            return null;
        }

        $this->resetFailedAttempts();
        return $decrypted;
    }

    /**
     * Decrypt the note's excerpt.
     */
    public function decryptExcerpt(string $code): ?string
    {
        $note = $this->note;
        if (!$note || !$note->excerpt) {
            return null;
        }

        return static::decryptString($note->excerpt, $code, $this->encryption_iv);
    }

    /**
     * Verify if a code matches the hashed code.
     */
    public function verifyCode(string $code): bool
    {
        return Hash::check($code, $this->encryption_code_hash);
    }
}
