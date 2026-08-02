<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Recipient
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->string('type'); // reply, new_comment, mention
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->nullableMorphs('notifiable');
            $table->enum('type', ['Success','Info','Warning','Error'])->default('Info');
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_notifications');
    }
};
