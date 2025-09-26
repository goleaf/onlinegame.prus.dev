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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['wood', 'clay', 'iron', 'crop']);
            $table->bigInteger('amount')->default(1000);
            $table->integer('production_rate')->default(10);
            $table->bigInteger('storage_capacity')->default(10000);
            $table->integer('level')->default(1);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();

            $table->unique(['village_id', 'type']);
            $table->index(['village_id', 'type', 'amount']);
        });

        // Resource production logs
        Schema::create('resource_production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['wood', 'clay', 'iron', 'crop']);
            $table->bigInteger('amount_produced');
            $table->bigInteger('amount_consumed')->default(0);
            $table->bigInteger('final_amount');
            $table->timestamp('produced_at');
            $table->timestamps();

            $table->index(['village_id', 'type', 'produced_at']);
        });

        // Market trades
        Schema::create('market_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->enum('offer_type', ['wood', 'clay', 'iron', 'crop']);
            $table->bigInteger('offer_amount');
            $table->enum('demand_type', ['wood', 'clay', 'iron', 'crop']);
            $table->bigInteger('demand_amount');
            $table->decimal('ratio', 10, 4);  // Exchange ratio
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'status']);
            $table->index(['village_id', 'status']);
            $table->index(['offer_type', 'demand_type', 'status']);
        });

        // Trade offers
        Schema::create('trade_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_trade_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('players')->onDelete('cascade');
            $table->bigInteger('amount_traded');
            $table->json('resources_exchanged');  // JSON for resources exchanged
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['buyer_id', 'completed_at']);
            $table->index(['seller_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_offers');
        Schema::dropIfExists('market_trades');
        Schema::dropIfExists('resource_production_logs');
        Schema::dropIfExists('resources');
    }
};
