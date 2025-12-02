<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_favorited')->default(false);
            $table->string('color', 7)->nullable(); // Hex color
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('group_id');
            $table->index('is_archived');
            $table->index('is_encrypted');
            $table->index('created_at');
            $table->unique(['user_id', 'slug']);

            // Composite indexes for frequent query patterns
            $table->index(['user_id', 'is_archived', 'created_at']);
            $table->index(['user_id', 'is_pinned', 'created_at']);
        });

        // Add fulltext index for search (MySQL specific)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE notes ADD FULLTEXT INDEX notes_fulltext (title, content)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
