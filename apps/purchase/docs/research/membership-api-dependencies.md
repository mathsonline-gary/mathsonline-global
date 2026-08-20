# Membership → purchase-web API/Data Dependency Catalog

> **Where this came from.** Research carried out in the `mathsonline-gary/purchase-web`
> repository, before this application moved into this monorepo. Its findings are unedited, including its
> links to that repository's issues (only the formatting is normalised to this repository's), because
> what it is worth is the _findings_ — what membership actually
> does today — and rewriting those to match later decisions would falsify them.
>
> It is **not a specification.** `packages/openapi-v2` is the only authority on the `/api/v2`
> contract. Where this document describes an endpoint, a field or an auth scheme the description does
> not have, the description wins and the difference is deliberate.
>
> Notably: the shared-secret `X-Api-Key` scheme, the `/markets` list endpoint, the `slug` market identity, `trial_mode`, the PayPal and Hotjar fields — none of these are in the description. What is catalogued here is membership's data, not this application's contract.

**Purpose**: This document catalogs, per order-creation flow, exactly what backend
data/API dependencies the Laravel app `membership` (repo: `/Users/gary/GitHub/membership`)
uses today to render its Blade-based checkout pages and to process order submissions.
It exists to inform the API contract between `membership` and the new Next.js app
`purchase-web`, which will replace membership's Blade-rendered checkout flow. Scope is
limited to the customer-facing order-creation flows listed in GitHub issue #2 (child of
wayfinder map issue #1): new (+homeschool/homeschool50), renewal (+homeschool), trial, awe,
gift (+homeschool), coupon-redemption, checkout, success. Admin/offline flows are explicitly
out of scope and not covered. All claims below are cited to actual file paths (and line
numbers where useful) in the `membership` repo — nothing here is inferred beyond what the
source shows.

Every in-scope flow sits behind `Route::middleware([RequireContextHasBrand::class])` in
`routes/web.php` (lines 17–71), and the brand itself is resolved earlier in the global
middleware stack by `app/Http/Middleware/InjectContext.php` (lines 32–54), which matches
the request host against `Brand::all()` (cached forever under `brands_with_setting`) and
sets `Context::add('brand_id', ...)` plus `$request->attributes->set('brand', $brand)`.
`RequireContextHasBrand` (`app/Http/Middleware/RequireContextHasBrand.php`) just 403s if no
brand was resolved. Every controller below reads the brand via
`$request->attributes->get('brand')` or `Context::get('brand_id')`.

---

## new / homeschool / homeschool50 / awe

These four all render the **same Blade view**, `resources/views/orders/create/new.blade.php`,
distinguished only by boolean view variables (`isHomeschool`, `awe`).

- **Blade view**: `resources/views/orders/create/new.blade.php` (348 lines)
- **Web controller**:
  - `App\Http\Controllers\Web\NewOrderController::create` — `app/Http/Controllers/Web/NewOrderController.php:17-42` (handles `new`, `homeschool`→redirects to `homeschool50`, `homeschool50`)
  - `App\Http\Controllers\Web\AweOrderController::create` — `app/Http/Controllers/Web/AweOrderController.php:15-34` (hardcodes `promotion_code: 'AWE'`, `is_recurring: false`)
- **Routes** (`routes/web.php`):
  - `GET /purchase` → `orders.create.new` (line 18-19)
  - `GET /purchase/homeschool` → `orders.create.new.homeschool` (line 21-22) — controller redirects this to `orders.create.new.homeschool50` preserving query string (`NewOrderController.php:19-21`)
  - `GET /purchase/homeschool50` → `orders.create.new.homeschool50` (line 24-25)
  - `GET /purchase/awe` → `orders.create.awe` (line 36-37)

### READ data

| Data item                                                                          | Source                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Brand                                                                              | `Context::get('brand_id')` (new/renewal share this) then `Brand::findOrFail($brand_id)` inside `OrderService::getOrderCreationData` — `app/Services/OrderService.php:89`                                                                                                                                                                                                                                                                       |
| Plans list (for pricing table)                                                     | `OrderService::getOrderCreationData()` — `app/Services/OrderService.php:76-123`. Resolves a `Campaign` (from renewal coupon, promotion, or `$brand->campaigns()->default()->first()`) then `$campaign->plans()->where('type', HOMESCHOOL\|STANDARD)->get()`. If `is_testing` query flag set and `$brand->setting->testing_plans_enabled`, uses `$brand->plans()->testing()` instead (line 100-102)                                             |
| Default plan (pre-selected)                                                        | Same call — `default_plan_id` resolved from `plans->where('code', $defaultPlanCode)->first()?->id` (line 117-119), driven by `?plan_id=` query param                                                                                                                                                                                                                                                                                           |
| Promotion validity (`?tr_id=`, `?code=`)                                           | `OrderService::findValidPromotion()` (private, `OrderService.php:918-935`) — `Brand::promotions()->whereCode($promoCode)->active()->first()` (Eloquent `Promotion` model, `active()` scope in `app/Models/Promotion.php:94-99`); for code `ORIG` additionally validates a signed nonce via `validateNonceCode()` (`OrderService.php:937-967`) using `$brand->setting->nonce_secret`                                                            |
| Recaptcha site key                                                                 | `$brand->setting->google_recaptcha_site_key` (Eloquent `BrandSetting`, `app/Models/BrandSetting.php`) — used directly in the view, e.g. `new.blade.php:204`                                                                                                                                                                                                                                                                                    |
| Brand display data (name, support_phone, id)                                       | Eloquent `Brand` model attributes, passed in as `'brand' => $request->attributes->get('brand')`                                                                                                                                                                                                                                                                                                                                                |
| AWE og:image / marketing_website                                                   | `$brand->marketing_website` (Brand model) — `new.blade.php:11`                                                                                                                                                                                                                                                                                                                                                                                 |
| Analytics/tag IDs (GA4, Google Ads, GTM, Hotjar, Facebook Pixel, conversion pixel) | `$brand->setting->{google_analytics_tag_id, google_ads_tag_id, google_tag_manager_container_id, hotjar_id, facebook_pixel_id, conversion_pixel_id}` — all `BrandSetting` fields, rendered via `resources/views/layouts/google_tag_script.blade.php`, `google_tag_manager_script.blade.php`, `hotjar_script.blade.php`, `conversion_pixel_script.blade.php`, `purchase.blade.php` (facebook pixel), included from `layouts/app.blade.php:30-43` |
| Organization JSON-LD (name, description, support_phone, social links)              | `Brand` model fields — `resources/views/layouts/organization_schema.blade.php`                                                                                                                                                                                                                                                                                                                                                                 |

No external MathsOnline API call is made on page render for this flow (only local `Brand`/`Plan`/`Promotion` Eloquent lookups).

### WRITE action

- **Route**: `POST /v1/orders/new` → `api.v1.orders.new.store`, `routes/api.php:22-23`
- **Controller**: `App\Http\Controllers\Api\Orders\NewOrderController::store` — `app/Http/Controllers/Api/Orders/NewOrderController.php:21-46`. Calls `OrderService::createNewOrder()` (`app/Services/OrderService.php:364-473`) with the validated payload plus `request_url` (from `Referer` header) and `ip_address` (from `$request->ip()`).
- **Form Request**: `App\Http\Requests\StoreNewOrderRequest` — `app/Http/Requests/StoreNewOrderRequest.php:26-75`

Payload fields (from `rules()`):

| Field                  | Rules                                                                                                                                                                               |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `brand_id`             | required, integer, must equal current brand id                                                                                                                                      |
| `success_url`          | required, url                                                                                                                                                                       |
| `plan_id`              | required, integer, must exist in `plans` table                                                                                                                                      |
| `email`                | required, email, max:255, **confirmed** (expects `email_confirmation` field too, per RHF/Laravel `confirmed` convention — the view sends `email_confirmation`, `new.blade.php:303`) |
| `first_name`           | required, string, max:255                                                                                                                                                           |
| `last_name`            | required, string, max:255                                                                                                                                                           |
| `promotion_code`       | nullable, string                                                                                                                                                                    |
| `nonce_code`           | nullable, string                                                                                                                                                                    |
| `agreement`            | required, accepted (boolean true)                                                                                                                                                   |
| `g_recaptcha_response` | required, custom `GoogleRecaptcha` rule keyed to `$brand->setting->google_recaptcha_secret_key`                                                                                     |

Response on success: `{ error: false, message: 'Ready to checkout', data: { order: <uuid> } }` (`NewOrderController.php:39-45`). Front end then redirects to `orders.checkout.show?oid=<uuid>` (`new.blade.php:322-323`).

Business logic inside `createNewOrder` worth noting for contract design: creates/updates `User`, saves customer to Stripe (`StripeClientFactoryInterface`), rejects with `TooManyPurchasesException` (409) if a paid order exists for the user within 5 minutes (`hasRecentPaidOrder`, `OrderService.php:904-916`), re-validates promotion + plan server-side, creates `Order` (status `CREATING`), then creates a Stripe Checkout Session (`stripeClient->createCheckoutSession`) — errors surface as `OrderCreationException` → HTTP 500.

### Shared components used

`orders.layouts.header` (brand logo), `orders.components.pricing_table` (passed `default_plan_id`, `plans`), `orders.components.sidebar` (passed `isHomeschool`, plus reads `$awe`/`$brand` from parent scope since it's an `@include` not isolated component), `orders.components.switch_country_modal`.

---

## renewal / renewal-homeschool

- **Blade view**: `resources/views/orders/create/renewal.blade.php` (295 lines)
- **Web controller**: `App\Http\Controllers\Web\RenewalOrderController::create` — `app/Http/Controllers/Web/RenewalOrderController.php:18-42`, plus a secondary AJAX endpoint `checkEligibility` (lines 44-75)
- **Routes**:
  - `GET /purchase/renew` → `orders.create.renewal` (`routes/web.php:27-28`)
  - `GET /purchase/homeschool/renew` → `orders.create.renewal.homeschool` (`routes/web.php:30-31`)
  - `POST /purchase/renewal/check-email` → `orders.renewal.check-email` (`routes/web.php:54-55`) — used for inline field-level validation while typing the email (jQuery Form Validator "server" module, `renewal.blade.php:119-121`)

### READ data

| Data item                                                | Source                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| -------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Plans                                                    | Same `OrderService::getOrderCreationData()` as new/homeschool, additionally passing `renewal_coupon_code` (from `?rc=` query param) — `RenewalOrderController.php:22-30`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Renewal coupon validity                                  | `OrderService::findValidRenewalCoupon()` (private, `OrderService.php:1033-1046`) → `RenewalCouponService::resolveRenewalCouponByCode($brand, $code)` (`app/Services/RenewalCouponService.php:60-125`). This first checks the local `RenewalCoupon` Eloquent model (`brand_id` + `code`); if not found locally, calls **MathsOnlineClient::findRenewalCouponByCode()** (external MathsOnline API, `GET {baseUrl}/api/renewal_coupons/getRenewalCouponByCode/{code}?countryHash={brand.hash}` — `app/Services/Integrations/MathsOnline/MathsOnlineClient.php:64-97`), then persists a new local `RenewalCoupon` row. Redeemability checked via `RenewalCoupon::isRedeemable()` (`expires_at` in future and not yet redeemed) |
| Promotion validity                                       | Same as new flow — local `Promotion` Eloquent lookup                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| Renewal email eligibility (AJAX, `check-email`)          | `OrderService::isEligibleForRenewalOrder($brand, $email)` (`OrderService.php:1018-1031`) → **MathsOnlineClient::findSubscriptionByEmail()** (external API, `GET {baseUrl}/api/getSubscriptionByEmail/{email}?countryHash={brand.hash}`, `MathsOnlineClient.php:32-62`) — returns `MathsOnlineSubscription`; eligible only if `subscription.brandId === brand.id`                                                                                                                                                                                                                                                                                                                                                           |
| Prefilled email (from renewal coupon owner or `?email=`) | `$data['renewal_coupon']?->owner->email` (Eloquent relation `RenewalCoupon::owner()` → `User`) or raw query param sanitized with `FILTER_SANITIZE_EMAIL` — `RenewalOrderController.php:40`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Recaptcha, brand settings, analytics                     | Same `BrandSetting`/`Brand` sources as new flow                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |

### WRITE actions

1. **Eligibility check** (not an order-creation POST, but a required pre-check): `POST /purchase/renewal/check-email` (web route, not versioned API) → `RenewalOrderController::checkEligibility` — validates `email` (required, email) inline via `Validator::make`, then calls `isEligibleForRenewalOrder`. Response: `{valid: bool, message?: string}`. On ineligible, message includes an HTML link built from `route('orders.create.new', $queryParams)` reusing the referer's query string plus the submitted email (`RenewalOrderController.php:63-73`).

2. **Order submission**: `POST /v1/orders/renewal` → `api.v1.orders.renewal.store` (`routes/api.php:24-25`)
   - **Controller**: `App\Http\Controllers\Api\Orders\RenewalOrderController::store` — `app/Http/Controllers/Api/Orders/RenewalOrderController.php:21-46`, calls `OrderService::createRenewalOrder()` (`OrderService.php:482-596`)
   - **Form Request**: `App\Http\Requests\StoreRenewalOrderRequest` — `app/Http/Requests/StoreRenewalOrderRequest.php:26-71`

Payload fields:

| Field                  | Rules                                                            |
| ---------------------- | ---------------------------------------------------------------- |
| `brand_id`             | required, integer, in `[brand.id]`                               |
| `success_url`          | required, url                                                    |
| `plan_id`              | required, integer, exists in `plans`                             |
| `email`                | required, email, max:255 (no `confirmed` here, unlike new-order) |
| `promotion_code`       | nullable, string                                                 |
| `nonce_code`           | nullable, string                                                 |
| `renewal_coupon_code`  | nullable, string                                                 |
| `agreement`            | required, accepted                                               |
| `g_recaptcha_response` | required, `GoogleRecaptcha` rule                                 |

`createRenewalOrder` business logic of note: re-checks `isEligibleForRenewalOrder` server-side (throws `InvalidOrderException` if not); if no local `User` exists, calls **MathsOnlineClient::findCustomerByEmail()** (external API `GET {baseUrl}/api/getCustomerByEmail/{email}?countryHash={brand.hash}`, `MathsOnlineClient.php:477-517`) to sync the customer record (`MathsOnlineCustomer::toUser()`); validates renewal coupon ownership (`RenewalCoupon::isOwnedBy($user)`); creates `Order` (type `RENEWAL`, status `CREATING`); creates Stripe Checkout Session.

### Shared components used

`orders.layouts.header`, `orders.components.pricing_table`, `orders.components.switch_country_modal`. (Note: `renewal.blade.php` does **not** include `orders.components.sidebar` — only new/homeschool/awe do.)

---

## trial

Trial has **two distinct modes**, selected server-side by `$brand->setting->trial_mode` (`App\Enums\TrialMode`: `GUEST` or `STRIPE`), each with its own Blade view but both reachable via the same route/controller.

- **Web controller**: `App\Http\Controllers\Web\TrialOrderController::create` — `app/Http/Controllers/Web/TrialOrderController.php:17-49`
- **Route**: `GET /purchase/trial` → `orders.create.trial` (`routes/web.php:33-34`)
- **Gate**: requires `?source=wp` query param, otherwise redirects to `/404` (`TrialOrderController.php:20-22`)

### Mode 1 — GUEST (`orders/create/trial_mode_1.blade.php`, 269 lines)

- Rendered when `$brand->setting->trial_mode === TrialMode::GUEST`
- Meant to run inside an iframe (posts height to `window.parent` — lines 160-175 of the view)
- View vars: `brand`, `flag` (pre-selects the "I am a" dropdown from `?flag=`)

### Mode 2 — STRIPE (`orders/create/trial_mode_2.blade.php`, 333 lines)

- Rendered when `$brand->setting->trial_mode === TrialMode::STRIPE`
- READ: `OrderService::getOrderCreationData(['brand_id', 'is_testing', 'is_recurring' => true])` (`TrialOrderController.php:35-39`) — same plan-resolution logic as new/renewal, filtered to recurring plans only. View computes `pricing.single`/`pricing.family` from `$plans` client-side in Blade (`trial_mode_2.blade.php:23-26`) to show an invoice preview with `price_original`, `currencySymbol`.
- Also runs inside an iframe (same postMessage height pattern).

If `trial_mode` is neither GUEST nor STRIPE, redirects to `/404` (`TrialOrderController.php:47`).

### WRITE action (both modes submit to the same endpoint)

- **Route**: `POST /v1/orders/trial` → `api.v1.orders.trial.store` (`routes/api.php:26-27`)
- **Controller**: `App\Http\Controllers\Api\Orders\TrialOrderController::store` — `app/Http/Controllers/Api/Orders/TrialOrderController.php:20-42`, calls `OrderService::createTrialOrder()` (`OrderService.php:616-629`), which dispatches to `createGuestTrialOrder` (private, `OrderService.php:1180-1278`) or `createStripeTrialOrder` (private, `OrderService.php:1287-1407`) based on `mode`.
- **Form Request**: `App\Http\Requests\StoreTrialOrderRequest` — `app/Http/Requests/StoreTrialOrderRequest.php:27-94` (rules are **conditional on `mode`**)

Common fields (both modes):

| Field                  | Rules                                                                      |
| ---------------------- | -------------------------------------------------------------------------- |
| `brand_id`             | required, integer, in `[brand.id]`                                         |
| `mode`                 | required, integer, in `TrialMode` case values (1=GUEST, 2=STRIPE per enum) |
| `first_name`           | required, string, max:255                                                  |
| `email`                | required, email, max:255                                                   |
| `agreement`            | required, accepted                                                         |
| `g_recaptcha_response` | required, `GoogleRecaptcha` rule                                           |

Mode-1 (GUEST) additional fields: `phone` (required, string, regex `^\+?[0-9\s\-()]+$`), `user_type` (required, string, in `['hs','parent','teacher']`).

Mode-2 (STRIPE) additional fields: `last_name` (required, string, max:255), `membership_type` (required, integer, in `[1,2]` — 1=Single,2=Family), `success_url` (required, url).

Business logic of note: GUEST mode creates a `User` with `prospect_type` derived from `user_type`, finds the recurring single-student plan on the default campaign, creates the `Order` immediately as **PAID** (`OrderStatus::PAID`, `paid_at = now()`) with no Stripe checkout — i.e., guest trial is free/instant. STRIPE mode instead calls **MathsOnlineClient::findSubscriptionByEmail()** to check the customer doesn't already have an active/expired subscription (throws `InvalidOrderException` with `IneligibleTrialOrderException::REASON_ACTIVE_SUBSCRIPTION` or `REASON_EXPIRED_SUBSCRIPTION` — these exact string constants are pattern-matched in the frontend JS, e.g. `trial_mode_1.blade.php:240-246`, `trial_mode_2.blade.php:304-310`, to show custom error copy with login/renewal links), then creates `Order` as `CREATING` and a Stripe Checkout Session.

### Shared components used

Neither trial view includes `orders.layouts.header`, `pricing_table`/`pricing_card`, `sidebar`, or `switch_country_modal` — they are standalone iframe-embeddable forms.

---

## gift / gift-homeschool

- **Blade view**: `resources/views/orders/create/gift.blade.php` (527 lines)
- **Web controller**: `App\Http\Controllers\Web\GiftOrderController::create` — `app/Http/Controllers/Web/GiftOrderController.php:15-37` (note: this controller class does **not** extend the base `Controller` — `GiftOrderController.php:9`)
- **Routes**:
  - `GET /purchase/gift` → `orders.create.gift` (`routes/web.php:39-40`)
  - `GET /purchase/gift/homeschool` → `orders.create.gift.homeschool` (`routes/web.php:42-43`)

### READ data

| Data item                                       | Source                                                                                                                                                                                                         |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Plans + default plan                            | `OrderService::getOrderCreationData(['brand_id','default_plan_code','is_homeschool','is_testing'])` — no promotion/nonce/renewal-coupon params passed for gift (`GiftOrderController.php:21-26`)               |
| Countries list (for address country `<select>`) | `App\ValueObjects\Country::all()` (`app/ValueObjects/Country.php`) — a static, hardcoded array of ~250 countries (`name`, `code_alpha_two`, `code_alpha_three`), no DB/API call — `GiftOrderController.php:35` |
| Prefilled recipient email                       | `?email=` query param, sanitized with `FILTER_SANITIZE_EMAIL` (`GiftOrderController.php:31`)                                                                                                                   |
| Google Maps API key (address autocomplete)      | `$brand->setting->google_maps_api_key` — `gift.blade.php:325`                                                                                                                                                  |
| PayPal config (url, account id, notify url)     | `$brand->setting->{paypal_url, paypal_account_id, paypal_notify_url}` — used to build a hidden PayPal classic-checkout form (`gift.blade.php:281-312`)                                                         |
| Brand logo/currency for PayPal form             | `Brand::{id, currency}`                                                                                                                                                                                        |
| Recaptcha, analytics                            | Same `BrandSetting` sources as other flows                                                                                                                                                                     |

### WRITE action

- **Route**: `POST /v1/orders/gift` → `api.v1.orders.gift.store` (`routes/api.php:30-31`)
- **Controller**: `App\Http\Controllers\Api\Orders\GiftOrderController::store` — `app/Http/Controllers/Api/Orders/GiftOrderController.php:19-65` (also does not extend a base controller), calls `OrderService::createGiftOrder()` (`OrderService.php:784-899`)
- **Form Request**: `App\Http\Requests\StoreGiftOrderRequest` — `app/Http/Requests/StoreGiftOrderRequest.php:26-56`

Payload fields:

| Field                  | Rules                                                                                                              |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `brand_id`             | required, integer, in `[brand.id]`                                                                                 |
| `plan_id`              | required, integer, exists in `plans` **scoped to this brand** (`Rule::exists(...)->where('brand_id', $brand->id)`) |
| `first_name`           | required, string                                                                                                   |
| `last_name`            | required, string                                                                                                   |
| `email`                | required, email, **confirmed**                                                                                     |
| `phone`                | required, string                                                                                                   |
| `address_line_one`     | required, string                                                                                                   |
| `address_line_two`     | nullable, string                                                                                                   |
| `address_city`         | required, string                                                                                                   |
| `address_state`        | required, string                                                                                                   |
| `address_postal_code`  | required, string                                                                                                   |
| `address_country`      | required, string                                                                                                   |
| `agreement`            | required, accepted                                                                                                 |
| `g_recaptcha_response` | required, `GoogleRecaptcha` rule                                                                                   |

Response shape is unique among the order-creation endpoints — it returns everything needed to auto-submit the hidden PayPal form client-side (`GiftOrderController.php:39-64`): `order_uuid`, `custom_id`, `plan_id`, `plan_recurring`, `plan_price`, `plan_frequency` (hardcoded `1`), `plan_period` (`'M'` or `'Y'`), `plan_description`, `address_line1/2/city/state/zip`, `phone`, `email`, `first_name`, `last_name`, `cmd_id` (`_xclick-subscriptions` or `_xclick`), `src_id` (`'1'`/`'0'`), `notify_url` (`$brand->setting->paypal_notify_url`). HTTP status `201 Created`.

Business logic: creates/updates local `User` + Stripe customer record (note: Stripe customer is saved even though gift payment actually goes through PayPal — this looks like a carried-over side effect, not something to necessarily replicate); checks `hasRecentPaidOrder`; validates plan against brand's default campaign; creates `Order` with `status = READY` (not `CREATING`, since payment happens externally via PayPal redirect) — no Stripe Checkout Session is created for gift orders.

### Shared components used

`orders.components.pricing_table`, `orders.components.switch_country_modal`. Does not include `orders.layouts.header` or `sidebar`.

---

## coupon-redemption

- **Blade view**: `resources/views/orders/create/coupon_redemption.blade.php` (430 lines)
- **Web controller**: `App\Http\Controllers\Web\CouponRedemptionOrderController::create` — `app/Http/Controllers/Web/CouponRedemptionOrderController.php:10-19`
- **Route**: `GET /subscribe` → `orders.create.coupon-redemption` (`routes/web.php:45-46`)

### READ data

| Data item             | Source                                                                                                                     |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Countries list        | `App\ValueObjects\Country::all()` (same static list as gift) — `CouponRedemptionOrderController.php:17`                    |
| Prefilled email       | `?email=` query param, `FILTER_SANITIZE_EMAIL`                                                                             |
| Prefilled coupon code | `?code=` query param, sanitized with `FILTER_UNSAFE_RAW` + strip-low/high flags (`CouponRedemptionOrderController.php:16`) |
| Google Maps API key   | `$brand->setting->google_maps_api_key` — `coupon_redemption.blade.php:276`                                                 |
| Recaptcha, analytics  | Same `BrandSetting` sources                                                                                                |

No plans/pricing data is fetched for this page — the view has no pricing table (the plan is implied by the coupon itself, resolved server-side on submit).

### WRITE action

- **Route**: `POST /v1/orders/coupon-redemption` → `api.v1.orders.coupon-redemption.store` (`routes/api.php:28-29`)
- **Controller**: `App\Http\Controllers\Api\Orders\CouponRedemptionOrderController::store` — `app/Http/Controllers/Api/Orders/CouponRedemptionOrderController.php:20-44` (does not extend a base controller), calls `OrderService::createCouponRedemptionOrder()` (`OrderService.php:638-775`)
- **Form Request**: `App\Http\Requests\StoreCouponRedemptionOrderRequest` — `app/Http/Requests/StoreCouponRedemptionOrderRequest.php:25-49`

Payload fields:

| Field                  | Rules                              |
| ---------------------- | ---------------------------------- |
| `brand_id`             | required, integer, in `[brand.id]` |
| `coupon_code`          | required, string                   |
| `first_name`           | required, string                   |
| `last_name`            | required, string                   |
| `email`                | required, email, **confirmed**     |
| `phone`                | required, string                   |
| `address_line_one`     | required, string                   |
| `address_line_two`     | nullable, string                   |
| `address_city`         | required, string                   |
| `address_state`        | required, string                   |
| `address_postal_code`  | required, string                   |
| `address_country`      | required, string                   |
| `agreement`            | required, accepted                 |
| `g_recaptcha_response` | required, `GoogleRecaptcha` rule   |

Business logic: looks up local `Coupon` model by `brand_id`+`code`, scoped `redeemable()` (not yet redeemed) — Eloquent only, no MathsOnline API call for coupon validation (`OrderService.php:660-667`); throws `InvalidOrderException('coupon_code', ...)` if not found/not redeemable; creates/updates `User` + Stripe customer; wraps `Order` creation + marking the `Coupon` as redeemed (`redeemed_by`, `redeemed_at`) in a `DB::transaction`; `Order` created directly as `status = PAID`, `plan_price = 0`, `plan_id = $coupon->plan_id` — i.e. no payment step at all for this flow. Response: `{error:false, data:{order: <uuid>}}`, HTTP 201.

### Shared components used

`orders.components.switch_country_modal` only. No header, no pricing table/sidebar (matches: no plan selection UI, price is implied 0 via the coupon).

---

## checkout

- **Blade view**: `resources/views/orders/checkout/show.blade.php` (70 lines)
- **Web controller**: `App\Http\Controllers\Web\OrderCheckoutController::show` — `app/Http/Controllers/Web/OrderCheckoutController.php:16-38`
- **Route**: `GET /checkout` → `orders.checkout.show` (`routes/web.php:48-49`), expects `?oid=<order-uuid>` query param

### READ data

| Data item                                              | Source                                                                                                                                                                                                                                                                                                                                                              |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Order (with Stripe transaction)                        | `OrderService::findOrderByUuid($uuid, ['with' => ['stripeTransaction']])` — `OrderService.php:134-149`; Eloquent `Order::where('uuid', $uuid)->with('stripeTransaction')->firstOrFail()`. 404-redirects if not found (`OrderNotFoundException`) or if `$order->brand_id !== Context::get('brand_id')` (cross-brand protection, `OrderCheckoutController.php:24-30`) |
| Stripe Checkout client secret                          | `$order->stripeTransaction->checkout_session_client_secret` (Eloquent relation on `Order`, populated when `StripeClientFactory`'s client called `createCheckoutSession` during order creation)                                                                                                                                                                      |
| Order status (drives which branch of the view renders) | `$order->status` (`App\Enums\OrderStatus`)                                                                                                                                                                                                                                                                                                                          |
| Stripe publishable key                                 | `$brand->setting->stripe_publishable_key` — `checkout/show.blade.php:50`                                                                                                                                                                                                                                                                                            |

This page is a thin wrapper around **Stripe's Embedded Checkout** JS SDK: it mounts `stripe.initEmbeddedCheckout({clientSecret})` into a `#checkout` div only when `$orderStatus === OrderStatus::READY`; for `PAID` it shows a success message with a login link; for `CANCELLED` shows an expired message; default/unknown shows "order not found" with links back to `orders.create.new` / `orders.create.renewal`. On Stripe's `onComplete` callback it redirects to `orders.checkout.success?oid=<uuid>` (`checkout/show.blade.php:53-64`).

### WRITE action

None — this page performs no order-creation POST itself. The actual payment submission happens inside Stripe's embedded iframe (against Stripe directly, not a membership API), and completion is detected client-side via the `onComplete` callback plus asynchronously via Stripe webhooks (`POST /v1/webhooks/stripe/{brand}` → `StripeWebhookController`, `routes/api.php:34-35` — out of scope for this catalog since it's not part of the browser-facing order-creation flows, but worth flagging: order status transitions to PAID happen via this webhook, not via any request the checkout page makes).

### Shared components used

`orders.layouts.header` only.

---

## success

- **Blade view**: `resources/views/orders/checkout/success.blade.php` (163 lines), extends `layouts.app` (not `layouts.purchase`)
- **Web controller**: `App\Http\Controllers\Web\OrderCheckoutController::success` — `app/Http/Controllers/Web/OrderCheckoutController.php:40-63`
- **Route**: `GET /purchase/success` → `orders.checkout.success` (`routes/web.php:51-52`), expects `?oid=<order-uuid>`

### READ data

| Data item                                                  | Source                                                                                                                                                                                                                                                                            |
| ---------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Order (with user + plan/item)                              | `OrderService::findOrderByUuid($uuid, ['with' => ['user', 'item']])` — same method as checkout, different eager-loads. Same brand-mismatch 404 guard as checkout (`OrderCheckoutController.php:44-53`)                                                                            |
| Order type (drives copy: TRIAL / RENEWAL / GIFT / default) | `$order->type` (`App\Enums\OrderType`)                                                                                                                                                                                                                                            |
| Order financials for GA4 ecommerce push                    | `$order->{uuid, total_price, sales_tax, referral_code}`, `$order->item->{stripe_id, description, type, currency, price}` — Eloquent `Order`/`Plan` (`item` relation) attributes, only rendered if `$brand->setting->google_analytics_tag_id` is set (`success.blade.php:124-161`) |
| Customer PII for GA4 `user_data`                           | `$order->user->{first_name, last_name, email, phone, address_city, address_state, address_postal_code, address_country}` — Eloquent `User` model via `Order::user()` relation                                                                                                     |
| Brand marketing website (trial CTA link)                   | `$brand->marketing_website`                                                                                                                                                                                                                                                       |
| Brand info_email                                           | `$brand->info_email`                                                                                                                                                                                                                                                              |

Note the controller comment explaining a known behavior worth carrying into the contract: "We allow it to show the success page for unpaid orders, because there is a delay of order status to be updated handled by the webhooks. For Google Pay and Apple Pay, it's necessary to show the success page immediately." (`OrderCheckoutController.php:56-58`) — i.e., success page does **not** gate on `order.status === PAID`.

### WRITE action

None — display-only page. No POST/API call originates from this page.

### Shared components used

None of the `orders.components.*` partials (uses `layouts.app` directly, not `layouts.purchase`); does inline its own logo image (`Vite::asset('resources/images/'.$brand->id.'/logo-white-300x72.png')`) rather than `orders.layouts.header`.

---

## Shared components

### `orders.layouts.header` — `resources/views/orders/layouts/header.blade.php` (5 lines)

Renders only the brand logo image, sourced from `Vite::asset('resources/images/'.$brand->id.'/logo-white-300x72.png')`.
Required prop: `$brand` (Eloquent `Brand`, needs `id`).
Used by: new/homeschool/homeschool50/awe (`new.blade.php:34`), renewal (`renewal.blade.php:25`), gift (does **not** use it — has its own inline logo in checkout success and the coupon-redemption view instead), checkout/show (`checkout/show.blade.php:6`). Not used by: trial (both modes), gift, coupon-redemption, success (success uses `layouts.app` + inline logo directly).

### `orders.components.pricing_table` — `resources/views/orders/components/pricing_table.blade.php` (32 lines)

Buckets the passed-in `$plans` collection into 4 slots — `single_monthly` (recurring, student_limit=1), `single_year` (non-recurring, student_limit=1), `family_monthly` (recurring, student_limit>1), `family_year` (non-recurring, student_limit>1) — then renders two rows ("Single Membership" / "Family Membership"), each including `pricing_card` twice.
Required props: `$plans` (Eloquent `Collection<Plan>`), `$default_plan_id` (nullable int, for highlighting the pre-selected plan and setting the `active` CSS class).
Used by: new/homeschool/homeschool50/awe (`new.blade.php:91`), renewal (`renewal.blade.php:137`), gift (`gift.blade.php:62`). Not used by: trial (computes its own single/family price display inline instead, without the interactive plan-selector cards), coupon-redemption, checkout, success.

### `orders.components.pricing_card` — `resources/views/orders/components/pricing_card.blade.php` (81 lines)

Renders one selectable plan tile: price (`price`, struck-through `price_original` if discounted via `Plan::price_original > Plan::price`), a "per month"/"N payments over N months"/"for 12 months (+N bonus months FREE)" caption driven by `Plan::isRecurring()`/`isInstallment()`/`billing_period` (`App\ValueObjects\BillingPeriod`), and a "Save $X" badge if `price_saved > 0`. Emits a `data-plan-id` attribute and toggles the `.active` class when `$pricing->id === $default_plan_id`; clicking is wired up by page-level JS (each page's own `<script>` block, not the component) to set the hidden `plan_id` form field.
Required props: `$pricing`(a single`Plan`model or null),`$default_plan_id`.
Used by: only via `pricing_table` (never included directly by a page).

### `orders.components.sidebar` — `resources/views/orders/components/sidebar.blade.php` (250 lines)

Renders: a homeschool discount banner image (if `$isHomeschool`), a brand-and-flow-specific testimonial block (switches on `$brand->id` and on `$isHomeschool`/`$awe` flags — hardcoded per-brand copy for brand ids 2, 4, 8), an AWE logo block (if `$awe`), a "Satisfaction Guaranteed" banner that opens a money-back-guarantee modal (uses `$brand->name`, `$brand->support_phone`), and a security/trust card (static copy).
Required props (read from parent Blade scope via `@include`, not passed explicitly except `isHomeschool`): `$isHomeschool` (explicitly passed), `$brand` (Eloquent `Brand`, implicit from parent view scope), `$awe` (implicit, only set truthy by the AWE controller).
Used by: new/homeschool/homeschool50/awe only (`new.blade.php:228`, passing `['isHomeschool' => $isHomeschool]`). Not used by renewal, trial, gift, coupon-redemption, checkout, or success.

### `orders.components.switch_country_modal` — `resources/views/orders/components/switch_country_modal.blade.php` (56 lines)

Entirely gated on `$brand->id === 4` (hardcoded brand check, not a `BrandSetting` flag). When applicable, renders a hidden modal asking "are you in Australia but on the US site?" and on load calls **`GET /v1/geoip.show`** (`App\Http\Controllers\Api\GeoIpController::show`, `app/Http/Controllers/Api/GeoIpController.php:15-58`) — this endpoint reads `$_SERVER['HTTP_X_FORWARDED_FOR']` (first entry = client IP behind the AWS ALB) and resolves country via `LocationServiceInterface::getCountryByIp()` (implementation not read in this pass — contract only needs the shape `{error, message, data: countryCode}`). If the response `data === 'AU'`, the modal is shown with a link to `https://www.mathsonline.com.au/purchase` and a "continue with US" dismiss button.
Required props: `$brand` (needs `id`).
Used by: new/homeschool/homeschool50/awe, renewal, trial (both modes), gift, coupon-redemption. Not used by: checkout, success.

---

## Cross-cutting notes for the API contract discussion

- **MathsOnlineClient** (`app/Services/Integrations/MathsOnline/MathsOnlineClient.php`, interface `app/Services/Contracts/MathsOnline/MathsOnlineClientInterface.php`) exposes 4 read/write-adjacent methods actually reached from the in-scope web/API controllers (via `OrderService`/`RenewalCouponService`): `findSubscriptionByEmail()` (renewal eligibility check + trial-stripe eligibility check), `findCustomerByEmail()` (renewal order user sync), `findRenewalCouponByCode()` (renewal coupon resolution), and indirectly `markRenewalCouponAsRedeemed()` (not called from any in-scope create/store flow directly — likely invoked elsewhere, e.g. webhook completion). `saveSubscription()` and `logPaymentTransaction()`/`logPayPalNotification()` are post-payment/webhook-side effects, not part of the page-render or order-submission request/response contract, but relevant context: they're what actually provisions the MathsOnline subscription after payment, which purchase-web will still depend on `membership` to do.
- **Form Requests are the single source of truth for payload shape.** All 5 in-scope create endpoints (`new`, `renewal`, `trial`, `coupon-redemption`, `gift`) validate `brand_id` (must match resolved brand), `agreement` (must be accepted), and `g_recaptcha_response` (custom `GoogleRecaptcha` rule against `$brand->setting->google_recaptcha_secret_key`) — these three fields are the one true constant across every order type.
- **`email` + `email_confirmation` "confirmed" pattern** appears in `new`, `gift`, and `coupon-redemption` Form Requests (not in `renewal` or `trial`) — i.e. the API expects `email_confirmation` field to be submitted alongside `email` for those three.
- No flow-specific Form Request currently validates `address_country` against the `Country` value-object list server-side (it's just `required|string`), so purchase-web has latitude on how strictly to validate this client-side.
- `Order` status semantics differ meaningfully by flow at creation time: `new`/`renewal`/`trial(stripe)` → `CREATING` (pending Stripe Checkout completion + webhook); `trial(guest)`/`coupon-redemption` → `PAID` immediately (no payment step); `gift` → `READY` (pending external PayPal redirect/IPN). This matters for what `checkout`/`success` pages need to branch on.
