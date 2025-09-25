<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('building_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->integer('building_type');
            $table->integer('level');
            $table->integer('position');
            $table->integer('queue_position'); // Position in the queue (1, 2, 3, etc.)
            $table->timestamp('start_time');
            $table->timestamp('finish_time');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('village_id');
            $table->index('finish_time');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_queue');
    }
};
