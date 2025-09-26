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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('tribe', ['roman', 'teuton', 'gaul', 'natars'])->default('roman');
            $table->integer('points')->default(0);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->integer('population')->default(0);
            $table->integer('villages_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'world_id']);
            $table->index(['world_id', 'points']);
            $table->index(['world_id', 'population']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
