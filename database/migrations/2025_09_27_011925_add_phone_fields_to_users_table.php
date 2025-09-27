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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('phone_country', 2)->nullable()->after('phone');
            $table->string('phone_normalized')->nullable()->after('phone_country');
            $table->string('phone_national')->nullable()->after('phone_normalized');
            $table->string('phone_e164')->nullable()->after('phone_national');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_country',
                'phone_normalized',
                'phone_national',
                'phone_e164'
            ]);
        });
    }
};
