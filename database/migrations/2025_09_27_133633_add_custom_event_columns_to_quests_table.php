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
        Schema::table('quests', function (Blueprint $table) {
            $table->boolean('isCustomEvent')->default(false)->after('is_active');
            $table->json('preloadedResolverData')->nullable()->after('isCustomEvent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['isCustomEvent', 'preloadedResolverData']);
        });
    }
};
