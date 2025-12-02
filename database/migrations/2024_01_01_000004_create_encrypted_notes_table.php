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
        Schema::create('encrypted_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('unique_code', 32)->unique();
            $table->longText('encrypted_content');
            $table->string('encryption_iv', 32);
            $table->string('hint', 255)->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('unique_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encrypted_notes');
    }
};
