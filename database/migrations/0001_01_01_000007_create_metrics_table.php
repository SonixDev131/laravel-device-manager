<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetricsTable extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('computer_id')->constrained()->onDelete('cascade');
            $table->string('hostname');

            $table->decimal('cpu_usage', 5, 2);
            $table->unsignedBigInteger('memory_total');
            $table->unsignedBigInteger('memory_used');
            $table->unsignedBigInteger('disk_total');
            $table->unsignedBigInteger('disk_used');

            $table->string('platform', 50);
            $table->string('platform_version', 50);

            $table->unsignedBigInteger('uptime');

            $table->timestampsTz();

            $table->index(['computer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
}
