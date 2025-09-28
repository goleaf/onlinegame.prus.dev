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
        Schema::table('villages', function (Blueprint $table) {
            // Add real-world coordinate columns
            $table->decimal('latitude', 10, 8)->nullable()->after('y_coordinate');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Add geohash for efficient spatial queries
            $table->string('geohash', 12)->nullable()->after('longitude');

            // Add elevation (optional)
            $table->decimal('elevation', 8, 2)->nullable()->after('geohash');

            // Add geographic metadata
            $table->json('geographic_metadata')->nullable()->after('elevation');

            // Add indexes for geographic queries
            $table->index(['latitude', 'longitude'], 'idx_villages_coordinates');
            $table->index('geohash', 'idx_villages_geohash');
            $table->index(['x_coordinate', 'y_coordinate'], 'idx_villages_game_coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->dropIndex('idx_villages_coordinates');
            $table->dropIndex('idx_villages_geohash');
            $table->dropIndex('idx_villages_game_coordinates');

            $table->dropColumn([
                'latitude',
                'longitude',
                'geohash',
                'elevation',
                'geographic_metadata',
            ]);
        });
    }
};
