<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->binary('credential_id');
            $table->binary('public_key');
            $table->bigInteger('counter')->unsigned()->default(0);
            $table->json('transports')->nullable();
            $table->timestamps();

            $table->index('credential_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
