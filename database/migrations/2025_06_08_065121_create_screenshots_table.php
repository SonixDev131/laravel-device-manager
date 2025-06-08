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
        Schema::create('screenshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('command_id');
            $table->uuid('computer_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->timestamp('taken_at');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('command_id')->references('id')->on('commands')->onDelete('cascade');
            $table->foreign('computer_id')->references('id')->on('computers')->onDelete('cascade');

            // Indexes
            $table->index(['command_id', 'computer_id']);
            $table->index('taken_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
