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
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->string('name');
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0);
            $table->integer('attack_power')->default(100);
            $table->integer('defense_power')->default(100);
            $table->integer('health')->default(1000);
            $table->integer('max_health')->default(1000);
            $table->json('special_abilities')->nullable();
            $table->json('equipment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['player_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
