<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
