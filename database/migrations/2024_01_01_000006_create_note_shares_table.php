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
        Schema::create('note_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('share_token', 64)->unique()->nullable();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('note_id');
            $table->index('shared_by_user_id');
            $table->index('shared_with_user_id');
            $table->unique(['note_id', 'shared_with_user_id']);

            // Composite index for querying shares received by a user
            $table->index(['shared_with_user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_shares');
    }
};
