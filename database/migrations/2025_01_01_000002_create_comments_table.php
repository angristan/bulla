<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();

            // Author info
            $table->string('author', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 512)->nullable();
            $table->boolean('is_admin')->default(false);

            // Content
            $table->text('body_markdown');
            $table->text('body_html');

            // Status: pending, approved, spam, deleted
            $table->string('status', 20)->default('pending');
            $table->boolean('email_verified')->default(false);

            // Engagement
            $table->unsignedInteger('upvotes')->default(0);
            $table->binary('voters_bloom')->nullable();

            // Notifications
            $table->boolean('notify_replies')->default(false);

            // Metadata
            $table->string('remote_addr', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('edit_token', 64)->nullable();
            $table->timestamp('edit_token_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['thread_id', 'status', 'created_at']);
            $table->index(['email', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
