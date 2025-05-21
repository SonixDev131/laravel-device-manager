<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('metrics', 'firewall_status')) {
            return;
        }

        Schema::table('metrics', function (Blueprint $table) {
            $table->json('firewall_status')->nullable()->after('uptime');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('metrics', 'firewall_status')) {
            return;
        }

        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn('firewall_status');
        });
    }
};
