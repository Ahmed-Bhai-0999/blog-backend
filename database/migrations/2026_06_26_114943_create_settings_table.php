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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('site_tagline')->nullable();
            $table->text('site_description')->nullable();
            $table->string('site_email')->nullable();
            $table->string('site_phone')->nullable();
            $table->text('site_address')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('copyright')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->string('timezone')->default('Asia/Karachi');
            $table->string('language')->default('English');
            $table->unsignedInteger('posts_per_page')->default(10);
            $table->boolean('allow_comments')->default(true);
            $table->enum('default_post_status',['Draft','Published','Scheduled','Archived'])->default('Draft');
            $table->text('google_analytics')->nullable();
            $table->text('google_search_console')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
