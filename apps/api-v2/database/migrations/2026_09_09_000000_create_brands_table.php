<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membership splits this across 'brands' and a 1:1 'brand_settings'; here it is one table.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('hash');
            $table->string('code', 20)
                ->unique()
                ->comment('The brand\'s identity, upper-case. e.g., "MOL_AU". Membership has no unique index on this; route binding here needs one.');
            $table->string('name');
            $table->string('description')
                ->nullable()
                ->comment('A brief description of the brand.');
            $table->string('market')
                ->comment('The market where the brand serves. e.g., "Australia", "United States", "Global"');
            $table->string('currency', 3)
                ->comment('The default currency code for the brand in alpha-3 format. e.g., "AUD", "USD", "GBP"');
            $table->string('domain')
                ->comment('The domain associated with the brand. e.g., "MathsOnline.com.au"');
            $table->string('marketing_website')
                ->comment('The marketing website URL of the brand. e.g., "https://www.mathsonline.com.au"');
            $table->string('info_email')
                ->comment('The email address for the brand. e.g., "info@mathsonline.com.au"');
            $table->string('feedback_email')
                ->comment('The email address for feedback. e.g., "feedback@mathsonline.com.au"');
            $table->string('noreply_email')
                ->comment('The email address for noreply. e.g., "noreply@mathsonline.com.au"');
            $table->string('tech_email')
                ->comment('The email address for the tech team. e.g., "tech@mathsonline.com.au"');
            $table->json('sales_emails')
                ->nullable()
                ->comment('The ordered email addresses of the sales team. The first one owns an enquiry, the rest are copied.');
            $table->string('support_phone')
                ->nullable()
                ->comment('The phone number for support. e.g., "123-456-7890"');
            $table->string('social_facebook')
                ->nullable()
                ->comment('The Facebook URL for the brand. e.g., "https://www.facebook.com/mathsonline"');
            $table->string('social_instagram')
                ->nullable()
                ->comment('The Instagram URL for the brand. e.g., "https://www.instagram.com/mathsonline"');

            // Nonce code settings
            $table->string('nonce_secret')
                ->nullable()
                ->comment('The secret for the nonce code verification.');

            // Purchase settings
            $table->tinyInteger('trial_mode')
                ->default(1)
                ->comment('The mode for the trial. 1 = simple guest, 2 = Stripe-driven');
            $table->boolean('testing_plans_enabled')
                ->default(false)
                ->comment('Whether the testing plans are enabled in the brand. This can be true only when doing testing.');

            // Stripe settings
            $table->string('stripe_publishable_key')
                ->nullable()
                ->comment('The publishable key for the linked Stripe account.');
            $table->string('stripe_secret_key')
                ->nullable()
                ->comment('The secret key for the linked Stripe account.');
            $table->string('stripe_webhook_secret')
                ->nullable()
                ->comment('The webhook secret for the linked Stripe account.');
            $table->boolean('stripe_test_mode')
                ->nullable()
                ->comment('Whether the brand is in test mode for Stripe.');

            // PayPal settings
            $table->string('paypal_url')
                ->nullable()
                ->comment('The URL for the linked PayPal account.');
            $table->string('paypal_account_id')
                ->nullable()
                ->comment('The account ID for the linked PayPal account.');
            $table->string('paypal_notify_url')
                ->nullable()
                ->comment('The URL to send webhook to.');

            // Keap settings
            $table->string('keap_account_key')
                ->nullable()
                ->comment('The account key for the linked Keap CRM account.');

            // Google recaptcha settings
            $table->string('google_recaptcha_site_key')
                ->nullable()
                ->comment('The site key for the linked Google recaptcha account.');
            $table->string('google_recaptcha_secret_key')
                ->nullable()
                ->comment('The secret key for the linked Google recaptcha account.');

            // Google Maps settings
            $table->string('google_maps_api_key')
                ->nullable();

            // Google Tag Manager settings
            $table->string('google_tag_manager_container_id')
                ->nullable();

            // Google Analytics 4 settings
            $table->string('google_analytics_tag_id')
                ->nullable();

            // Google Ads settings
            $table->string('google_ads_tag_id')
                ->nullable();

            // Conversion pixel settings
            $table->string('conversion_pixel_id')
                ->nullable();

            // Hotjar settings
            $table->string('hotjar_id')
                ->nullable();

            // Facebook Pixel settings
            $table->string('facebook_pixel_id')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
