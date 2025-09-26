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
        Schema::create('attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('target_village_id')->constrained('villages')->onDelete('cascade');
            $table->foreignId('attacker_village_id')->constrained('villages')->onDelete('cascade');
            $table->integer('attack_type'); // 1=attack, 2=raid, 3=reinforce
            $table->json('troops'); // JSON of troop types and counts
            $table->timestamp('arrival_time');
            $table->timestamp('return_time')->nullable();
            $table->boolean('is_returning')->default(false);
            $table->string('status', 20)->default('traveling'); // traveling, arrived, returning, completed
            $table->timestamps();

            // Indexes
            $table->index('attacker_id');
            $table->index('target_village_id');
            $table->index('attacker_village_id');
            $table->index('arrival_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attacks');
    }
};
