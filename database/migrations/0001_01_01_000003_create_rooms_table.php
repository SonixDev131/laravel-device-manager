<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->text('name')->unique();
            $table->integer('grid_rows');
            $table->integer('grid_cols');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
