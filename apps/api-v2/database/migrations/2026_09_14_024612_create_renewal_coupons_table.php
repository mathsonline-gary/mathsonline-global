<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membership's 'renewal_coupons' without 'user_id' and 'external_id'. showPricing looks a
     * coupon up by brand and code and asks whether it is redeemable, never who owns it: the owner
     * is enforced at order creation, and the external id exists only for the read-through sync
     * against the MathsOnline core system. Both return when authentication and that sync do.
     */
    public function up(): void
    {
        Schema::create('renewal_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id');
            $table->foreignId('campaign_id')
                ->comment('The campaign this coupon prices the table with.');
            $table->string('code')
                ->comment('The code issued to one customer for one renewal.');
            $table->timestamp('expires_at')
                ->comment('When the coupon expires. NOT NULL, as in membership, which dereferences it unguarded.');
            $table->timestamp('redeemed_at')
                ->nullable()
                ->comment('When the coupon was redeemed. NULL while it is still spendable.');
            $table->timestamps();

            $table->unique(['brand_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewal_coupons');
    }
};
