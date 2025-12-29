<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
