<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->enum('reason', ['Spam', 'Harassment', 'Abuse', 'Other']);
            $table->foreignId('reported_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('guest_token')->nullable();
            $table->enum('status', ['Pending', 'Reviewed', 'Actioned'])->default('Pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            // Avoid double reporting
            $table->unique(['comment_id', 'reported_by'], 'comment_user_report_unique');
            $table->unique(['comment_id', 'guest_token'], 'comment_guest_report_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reports');
    }
};
