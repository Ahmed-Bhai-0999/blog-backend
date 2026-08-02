<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('guest_token')->nullable();
            $table->tinyInteger('reaction'); // 0 = Dislike, 1 = Like, etc.
            $table->timestamps();

            // Indexing for performance and preventing duplicates
            $table->unique(['comment_id', 'user_id'], 'comment_user_reaction_unique');
            $table->unique(['comment_id', 'guest_token'], 'comment_guest_reaction_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
