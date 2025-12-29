<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 32); // isso, disqus, wordpress
            $table->string('source_id');
            $table->string('target_type', 32); // thread, comment
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['source', 'source_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
    }
};
