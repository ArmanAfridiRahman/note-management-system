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
        Schema::table('encrypted_notes', function (Blueprint $table) {
            // Drop the old unique_code column and its index
            $table->dropIndex('encrypted_notes_unique_code_index');
            $table->dropColumn('unique_code');

            // Add new column for hashed encryption code (bcrypt ~60 chars)
            $table->string('encryption_code_hash', 255)->after('note_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encrypted_notes', function (Blueprint $table) {
            $table->dropColumn('encryption_code_hash');
            $table->string('unique_code', 32)->unique()->after('note_id');
            $table->index('unique_code');
        });
    }
};
