<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membership's 'plans', keeping its name: a plan is what this side stores, and pricing is
     * only what the wire calls it. The columns are membership's minus the ones only a checkout
     * needs — 'stripe_id' and 'description' come back when a checkout endpoint asks for them.
     *
     * Five columns membership leaves nullable are NOT NULL here, because the Pricing schema the
     * wire declares has no null for any of them. Coalescing a stored null once, on the way in,
     * beats doing it on every read.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->index();
            $table->string('code', 15)
                ->comment('Identifies this plan to a checkout. Nullable in membership; required on the wire, so NOT NULL here. Opaque — "M3" is a family plan for five students, so neither the letter nor the digit is a fact about it.');

            // Money. Decimal amounts in 'currency', never minor units, because that is how
            // membership stores them.
            $table->decimal('price', 10, 2)
                ->comment('What the customer is charged, per billing period when recurring.');
            $table->decimal('price_original', 10, 2)
                ->comment('The undiscounted price. Equal to price when nothing is discounted.');
            $table->decimal('price_saved', 10, 2)
                ->default(0)
                ->comment('What the customer saves. Membership stores it rather than deriving it, and stores NULL for "nothing"; 0 here. Not always price_original - price, and never recomputed from them.');
            $table->string('currency', 3)
                ->default('usd')
                ->comment('This plan\'s own currency, lower-case as membership stores it. Upper-cased by PricingResource, because the wire says ISO 4217 alpha-3 upper-case.');

            // Billing.
            $table->boolean('is_recurring')
                ->comment('Whether price is charged every billing period until cancelled.');
            $table->string('billing_period_interval')
                ->default('month')
                ->comment('day, week, month or year.');
            $table->unsignedTinyInteger('billing_period_count')
                ->default(1)
                ->comment('How many intervals the customer pays for. Nullable in membership, required on the wire.');
            $table->unsignedTinyInteger('billing_period_extra_count')
                ->default(0)
                ->comment('Bonus intervals granted free on top of the count. The membership runs for count + extra_count.');
            $table->unsignedInteger('installment_count')
                ->default(0)
                ->comment('How many payments the price is split into, 0 when paid in one. An installment plan is not recurring.');

            $table->unsignedInteger('student_limit')
                ->default(1)
                ->comment('How many students the membership covers. 1 is single, anything greater is family — the only thing that decides which group of the wire\'s PricingTable a plan lands in.');
            $table->unsignedTinyInteger('type')
                ->comment('App\Enums\PlanType. 1 standard, 2 homeschool, 3 custom, 4 deprecated, 5 testing.');
        });

        Schema::create('campaign_plan', function (Blueprint $table) {
            $table->foreignId('campaign_id')->references('id')->on('campaigns');
            $table->foreignId('plan_id')->references('id')->on('plans');

            $table->index('campaign_id');
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_plan');
        Schema::dropIfExists('plans');
    }
};
