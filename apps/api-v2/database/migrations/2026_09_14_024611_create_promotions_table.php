<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membership's 'promotions' without 'redemption_count': nothing gates on it, and showPricing
     * never writes one. The brand+code index is unique here where membership's is plain, so
     * resolving a code cannot pick arbitrarily between duplicates.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id');
            $table->foreignId('campaign_id')
                ->comment('The campaign this promotion prices the table with.');
            $table->string('code', 10)
                ->comment('The code a customer sends. Anyone may use it, unlike a renewal coupon.');
            $table->timestamp('expires_at')
                ->nullable()
                ->comment('Expiration date. NULL never expires.');
            $table->timestamps();

            $table->unique(['brand_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
