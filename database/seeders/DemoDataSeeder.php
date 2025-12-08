<?php

namespace Database\Seeders;

use App\Models\EncryptedNote;
use App\Models\Group;
use App\Models\Note;
use App\Models\NoteShare;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seeder data from config.
     */
    protected array $config;

    /**
     * Created users for reference.
     */
    protected array $users = [];

    /**
     * Created notes grouped by user for sharing.
     */
    protected array $notesByUser = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->config = config('seeder_data');

        $this->command->info('Starting Demo Data Seeder...');

        // Clean existing data
        $this->cleanDatabase();

        // Create users
        $this->createUsers();

        // Create tags for each user
        $this->createTags();

        // Create groups for each user
        $this->createGroups();

        // Create notes for each user
        $this->createNotes();

        // Create shares between users
        $this->createShares();

        $this->command->info('Demo Data Seeder completed successfully!');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Users', count($this->users)],
                ['Tags', Tag::count()],
                ['Groups', Group::count()],
                ['Notes', Note::count()],
                ['Encrypted Notes', EncryptedNote::count()],
                ['Shares', NoteShare::count()],
            ]
        );
    }

    /**
     * Clean existing seeded data.
     */
    protected function cleanDatabase(): void
    {
        $this->command->info('Cleaning existing data...');

        // Disable foreign key checks for clean truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate tables in order
        NoteShare::truncate();
        EncryptedNote::truncate();
        DB::table('note_tag')->truncate();
        Note::withTrashed()->forceDelete();
        Tag::truncate();
        Group::truncate();
        User::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Database cleaned.');
    }

    /**
     * Create all users.
     */
    protected function createUsers(): void
    {
        $this->command->info('Creating users...');

        $password = $this->config['default_password'];

        foreach ($this->config['users'] as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $password,
                'color' => $userData['color'],
                'email_verified_at' => now(),
            ]);

            $this->users[] = $user;
        }

        $this->command->info('Created ' . count($this->users) . ' users.');
    }

    /**
     * Create tags for each user.
     */
    protected function createTags(): void
    {
        $this->command->info('Creating tags...');

        foreach ($this->users as $user) {
            foreach ($this->config['tags'] as $tagData) {
                Tag::create([
                    'user_id' => $user->id,
                    'name' => $tagData['name'],
                    'color' => $tagData['color'],
                ]);
            }
        }

        $this->command->info('Created ' . Tag::count() . ' tags.');
    }

    /**
     * Create groups for each user.
     */
    protected function createGroups(): void
    {
        $this->command->info('Creating groups...');

        foreach ($this->users as $user) {
            foreach ($this->config['groups'] as $groupData) {
                Group::create([
                    'user_id' => $user->id,
                    'name' => $groupData['name'],
                    'description' => $groupData['description'],
                    'color' => $groupData['color'],
                ]);
            }
        }

        $this->command->info('Created ' . Group::count() . ' groups.');
    }

    /**
     * Create notes for each user with random flags.
     */
    protected function createNotes(): void
    {
        $this->command->info('Creating notes...');

        $encryptionCode = $this->config['encryption_code'];

        foreach ($this->users as $user) {
            $userTags = Tag::where('user_id', $user->id)->get();
            $userGroups = Group::where('user_id', $user->id)->get();
            $userNotes = [];

            // Define which notes get which flags
            // Indices: 0-1 encrypted, 2-3 pinned, 4-5 favorited, 6-7 shared
            // Some overlap: 0 is encrypted+pinned, 1 is encrypted+favorited
            $encryptedIndices = [0, 1, 8, 9]; // 4 encrypted (2 with overlap)
            $pinnedIndices = [0, 2, 3];       // 3 pinned (1 with overlap)
            $favoritedIndices = [1, 4, 5];    // 3 favorited (1 with overlap)
            $shareableIndices = [2, 3, 6, 7]; // 4 shareable

            foreach ($this->config['notes'] as $index => $noteData) {
                $isEncrypted = in_array($index, $encryptedIndices);
                $isPinned = in_array($index, $pinnedIndices);
                $isFavorited = in_array($index, $favoritedIndices);

                // Get a random group for some notes (30% chance)
                $groupId = null;
                if (rand(1, 100) <= 30 && $userGroups->isNotEmpty()) {
                    $groupId = $userGroups->random()->id;
                }

                // Prepare note data
                $noteContent = $noteData['content'];
                $noteExcerpt = Note::generateExcerpt($noteContent);
                $encryptionHint = $noteData['encryption_hint'] ?? 'Four digits';

                // Handle encryption
                if ($isEncrypted) {
                    $encryptedData = EncryptedNote::encryptNoteData($noteContent, $noteExcerpt, $encryptionCode);
                    $noteContent = $encryptedData['encrypted_content'];
                    $noteExcerpt = $encryptedData['encrypted_excerpt'];
                }

                // Create the note
                $note = Note::create([
                    'user_id' => $user->id,
                    'group_id' => $groupId,
                    'title' => $noteData['title'],
                    'content' => $noteContent,
                    'excerpt' => $noteExcerpt,
                    'is_encrypted' => $isEncrypted,
                    'is_pinned' => $isPinned,
                    'is_favorited' => $isFavorited,
                    'is_archived' => false,
                    'color' => $noteData['color'] ?? $this->getRandomColor(),
                    'open_count' => rand(0, 50),
                ]);

                // Create encrypted note record if encrypted
                if ($isEncrypted) {
                    EncryptedNote::create([
                        'note_id' => $note->id,
                        'encryption_code_hash' => Hash::make($encryptionCode),
                        'encrypted_content' => $encryptedData['encrypted_content'],
                        'encryption_iv' => $encryptedData['encryption_iv'],
                        'hint' => $encryptionHint,
                        'failed_attempts' => 0,
                    ]);
                }

                // Attach random tags (1-4 tags per note)
                $tagCount = rand(1, 4);
                $randomTags = $userTags->random(min($tagCount, $userTags->count()));
                $note->tags()->attach($randomTags->pluck('id'));

                // Store note for sharing if shareable
                if (in_array($index, $shareableIndices)) {
                    $userNotes[] = $note;
                }
            }

            $this->notesByUser[$user->id] = $userNotes;
        }

        $this->command->info('Created ' . Note::count() . ' notes.');
        $this->command->info('Created ' . EncryptedNote::count() . ' encrypted notes.');
    }

    /**
     * Create shares between users.
     * Each user shares at least 2 notes with 1-5 other users.
     */
    protected function createShares(): void
    {
        $this->command->info('Creating shares...');

        $permissions = ['view', 'edit'];

        foreach ($this->users as $user) {
            $userNotes = $this->notesByUser[$user->id] ?? [];

            if (empty($userNotes)) {
                continue;
            }

            // Get other users to share with
            $otherUsers = collect($this->users)->filter(fn($u) => $u->id !== $user->id);

            // Share at least 2 notes
            $notesToShare = array_slice($userNotes, 0, min(2, count($userNotes)));

            foreach ($notesToShare as $note) {
                // Share with 1-5 random users
                $shareCount = rand(1, min(5, $otherUsers->count()));
                $usersToShareWith = $otherUsers->random($shareCount);

                foreach ($usersToShareWith as $targetUser) {
                    // Check if share already exists
                    $existingShare = NoteShare::where('note_id', $note->id)
                        ->where('shared_with_user_id', $targetUser->id)
                        ->exists();

                    if ($existingShare) {
                        continue;
                    }

                    NoteShare::create([
                        'note_id' => $note->id,
                        'shared_by_user_id' => $user->id,
                        'shared_with_user_id' => $targetUser->id,
                        'permission' => $permissions[array_rand($permissions)],
                        'message' => $this->getShareMessage(),
                        'expires_at' => rand(0, 1) ? now()->addDays(rand(7, 30)) : null,
                    ]);
                }
            }
        }

        $this->command->info('Created ' . NoteShare::count() . ' shares.');
    }

    /**
     * Get a random color from the config.
     */
    protected function getRandomColor(): ?string
    {
        $colors = $this->config['note_colors'];
        return $colors[array_rand($colors)];
    }

    /**
     * Get a random share message.
     */
    protected function getShareMessage(): ?string
    {
        $messages = [
            'Here are my notes for you to review.',
            'Take a look at this when you have time.',
            'Sharing this for your reference.',
            'Let me know your thoughts on this.',
            'FYI - relevant to our discussion.',
            null, // Sometimes no message
            null,
        ];

        return $messages[array_rand($messages)];
    }
}
