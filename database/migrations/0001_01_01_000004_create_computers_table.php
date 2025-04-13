<?php

declare(strict_types=1);

use App\Enums\ComputerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            // $table->text('name')->nullable();
            $table->string('mac_address', 25)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->integer('pos_row');
            $table->integer('pos_col');
            $table->string('status')->default(ComputerStatus::OFFLINE);
            $table->timestamp('last_seen_at')->nullable();
            $table->jsonb('system_metrics')->nullable()->comment('System metrics reported by agent (CPU, RAM, etc.)');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
