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
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('x_coordinate');
            $table->integer('y_coordinate');
            $table->integer('population')->default(0);
            $table->integer('culture_points')->default(0);
            $table->boolean('is_capital')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('wood')->default(1000);
            $table->integer('clay')->default(1000);
            $table->integer('iron')->default(1000);
            $table->integer('crop')->default(1000);
            $table->integer('wood_capacity')->default(1000);
            $table->integer('clay_capacity')->default(1000);
            $table->integer('iron_capacity')->default(1000);
            $table->integer('crop_capacity')->default(1000);
            $table->timestamps();

            $table->unique(['world_id', 'x_coordinate', 'y_coordinate']);
            $table->index(['player_id', 'world_id']);
            $table->index(['x_coordinate', 'y_coordinate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
