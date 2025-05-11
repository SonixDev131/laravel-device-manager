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
        Schema::create('agent_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('file_hash', 64);
            $table->string('version');
            $table->boolean('is_latest')->default(false);
            $table->unsignedBigInteger('size');

            $table->index(['file_hash', 'version']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_packages');
    }
};
