<?php

declare(strict_types=1);

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
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('github_id', 64)->nullable()->after('email');
            $table->string('github_username', 64)->nullable()->after('github_id');
            $table->index('github_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex(['github_id']);
            $table->dropColumn(['github_id', 'github_username']);
        });
    }
};
