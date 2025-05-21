<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('commands', 'error')) {
            return;
        }

        Schema::table('commands', function (Blueprint $table) {
            $table->string('error')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('commands', 'error')) {
            return;
        }

        Schema::table('commands', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
