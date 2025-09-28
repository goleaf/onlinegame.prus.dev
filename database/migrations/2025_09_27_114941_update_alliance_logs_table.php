<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alliance_logs', function (Blueprint $table) {
            // Add new columns
            if (! Schema::hasColumn('alliance_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('data');
            }
            if (! Schema::hasColumn('alliance_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('alliance_logs', 'reference_number')) {
                $table->string('reference_number', 50)->unique()->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alliance_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'reference_number']);
        });
    }
};
