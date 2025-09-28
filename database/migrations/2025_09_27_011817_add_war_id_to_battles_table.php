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
        Schema::table('battles', function (Blueprint $table) {
            $table->foreignId('war_id')->nullable()->constrained('alliance_wars')->onDelete('set null');
            $table->index('war_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            $table->dropForeign(['war_id']);
            $table->dropIndex(['war_id']);
            $table->dropColumn('war_id');
        });
    }
};
