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
        if (Schema::hasColumn('commands', 'output')) {
            return;
        }

        Schema::table('commands', function (Blueprint $table) {
            $table->text('output')->nullable()->after('error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('commands', 'output')) {
            return;
        }

        Schema::table('commands', function (Blueprint $table) {
            $table->dropColumn('output');
        });
    }
};
