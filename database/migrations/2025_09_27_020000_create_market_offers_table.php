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
        Schema::create('market_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->json('offering'); // Resources being offered
            $table->json('requesting'); // Resources being requested
            $table->decimal('ratio', 8, 2)->default(1.00); // Exchange ratio
            $table->integer('fee')->default(0); // Market fee in crop
            $table->enum('status', ['active', 'completed', 'cancelled', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable(); // Offer expiration
            $table->timestamp('completed_at')->nullable(); // When offer was completed
            $table->timestamp('cancelled_at')->nullable(); // When offer was cancelled
            $table->foreignId('buyer_village_id')->nullable()->constrained('villages')->onDelete('set null');
            $table->integer('quantity_traded')->nullable(); // How many units were traded
            $table->string('reference_number')->unique(); // Unique reference number
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'expires_at']);
            $table->index(['player_id', 'status']);
            $table->index(['village_id', 'status']);
            $table->index(['buyer_village_id']);
            $table->index(['ratio']);
            $table->index(['created_at']);
            $table->index(['completed_at']);
            $table->index(['cancelled_at']);
            
            // JSON indexes for resource filtering
            $table->index(['offering'], 'idx_offering');
            $table->index(['requesting'], 'idx_requesting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_offers');
    }
};
