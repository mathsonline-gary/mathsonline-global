# Country-switch-modal / currency-per-brand behavior (`membership`)

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
> Notably: the country switcher this describes needs a list of every served market, which the description does not offer, so nothing here is built.

Research for wayfinder issue [#13](https://github.com/mathsonline-gary/purchase-web/issues/13)
(child of map issue #1). Scope: read-only investigation of
`membership`'s `resources/views/orders/components/switch_country_modal.blade.php`
and everything that wires it up. All paths below are relative to
`/Users/gary/GitHub/membership` unless stated otherwise.

## Summary

The "switch country" modal is a purely client-side, cosmetic nudge that
appears **only on the US brand's** checkout pages, **only when a
third-party GeoIP lookup says the visitor is in Australia**. It offers
two choices: a plain `<a href>` link to the AU marketing site's
`/purchase` page (a full cross-domain navigation — no query params,
cookies, or session state are carried over), or a "continue" button
that just closes the modal client-side (`modal.hide()`, no
network call). It does not touch currency, brand, or pricing directly.
All of that is decided **before** the modal ever runs, by
`InjectContext` middleware resolving `Brand` from the **request host**
on every request. Switching "country" only works because the AU link
points at a different domain that resolves to a different `Brand` row
(and thus a different currency) on the next full page load.

## 1. What triggers the modal

File: `resources/views/orders/components/switch_country_modal.blade.php`

- The whole partial is gated by a **hardcoded brand-id check** in the
  Blade template itself:

  ```blade
  @if($brand->id === 4)
  ```

  (line 1). `$brand` is the `Brand` model instance injected into the
  parent view (see §4) — id `4` is the `MOL_US` brand (seeded in
  `database/seeders/BrandSeeder.php` lines 70-87: `code => 'MOL_US'`,
  `market => 'United States'`, `currency => 'USD'`, `domain =>
'localhost:8004'` in this dev seed). So the modal only ever renders
  markup on the US brand's checkout pages; on every other brand it
  renders nothing.

- On page load, an inline `<script defer>` block (lines 37-55) fires an
  AJAX GET to a GeoIP endpoint and only **shows** the modal if that
  lookup says the client is in Australia:

  ```js
  $.ajax({
      method: 'GET',
      url: '{{ route('api.v1.geoip.show') }}',
      success: function (response) {
          if (response.data === 'AU') {
              modal.show();
          }
      },
  });
  ```

  (lines 41-49). The modal `<div id="switchCountry">` starts with a
  `hidden` class (line 2) and is only unhidden by this JS callback —
  there is no server-rendered conditional based on the GeoIP result;
  the div is always in the DOM (for brand 4) and toggled purely
  client-side.

- It is **not** a link/button the user clicks to open it — it is
  auto-triggered on page load, conditional on brand id 4 + GeoIP
  result "AU".

- Included from six checkout page templates via `@include`:
  `resources/views/orders/create/{new,gift,coupon_redemption,renewal,trial_mode_1,trial_mode_2}.blade.php`
  (verified via `grep -rn "switch_country_modal" resources/`).

## 2. What the customer can actually change via it

Looking at the actual form/markup (lines 22-31), there is **no form
and no field** — just two static action elements:

```blade
<a href="https://www.mathsonline.com.au/purchase" target="_blank" ...>
    <img src="{{ Vite::asset('resources/images/flag-au.jpg') }}" alt="AU curriculum" ...>
    <div class="text-center">Switch to Australian curriculum</div>
</a>
<button id="USCurriculum" type="button" ...>
    <img src="{{ Vite::asset('resources/images/flag-us.jpg') }}" alt="AU curriculum" ...>
    <span class="text-center">Continue for the US curriculum</span>
</button>
```

- Option A — **"Switch to Australian curriculum"**: a plain anchor tag
  with a hardcoded, absolute `href="https://www.mathsonline.com.au/purchase"`
  and `target="_blank"`. No query string, no plan/promo params are
  forwarded from the current page.
- Option B — **"Continue for the US curriculum"**: a `<button
type="button" id="USCurriculum">` with no `href`/`action` at all.

So the only thing the customer can "change" is which **brand/domain**
they end up browsing on next (by navigating away entirely) — there is
no in-page control for country, currency, or language as independent
fields. Currency/curriculum/market are all bundled implicitly inside
"which brand's domain you're on."

The modal's copy (lines 11-13) also states the consequence in plain
language: _"This purchase will be charged in **US $** and will give
you access to the US curriculum only,"_ confirming currency and
curriculum content are brand-bundled, not independently selectable.

## 3. Where it submits to / what happens on click

- **AU option**: not a form submission — it's a normal `<a href>`
  navigation (opens in a new tab, `target="_blank"`) straight to
  `https://www.mathsonline.com.au/purchase`. That is a different
  top-level domain/app instance entirely. No query params, cookies,
  or session data are attached by this link; it is a bare URL.
- **US option**: the inline script's second handler is purely a UI
  no-op:

  ```js
  $("#USCurriculum").on("click", function () {
    modal.hide();
  });
  ```

  (lines 51-53). No AJAX call, no redirect, no cookie/session write —
  it just hides the modal `<div>` and the user remains on the current
  US-brand page/URL with whatever they'd already filled in.

There is **no controller, no route, and no session/cookie update**
backing either button. The only network call anywhere in this
component is the GET to `api.v1.geoip.show` used to decide whether to
_show_ the modal (see `routes/api.php` line 40:
`Route::get('/geoip', [GeoIpController::class, 'show'])->name('geoip.show');`,
outside any brand-context middleware group).

`GeoIpController::show()` (`app/Http/Controllers/Api/GeoIpController.php`)
reads `X-Forwarded-For` off `$_SERVER`, extracts the client IP, and
calls `LocationService::getCountryByIp()`
(`app/Services/LocationService.php`), which hits the third-party
`https://api.country.is/{ip}` API and returns a `Country` value object;
the controller responds with `{ data: <ISO alpha-2 code> }` (e.g.
`"AU"`). This is a stateless, per-request geolocation lookup — nothing
is cached/stored server-side for this feature.

## 4. How the choice affects pricing/checkout downstream

**Brand (and therefore currency) is resolved purely by request host,
independent of and prior to this modal** — the modal is layered on top
as a soft UX nudge, not a mechanism that itself changes brand/currency.

- `app/Http/Middleware/InjectContext.php` (lines 32-54) runs on every
  request, looks at `$request->getHttpHost()`, matches it against all
  cached `Brand` rows by domain suffix (`Str::endsWith($host,
strtolower($brand->domain))`, line 46), and — on match — sets
  `Context::add('brand_id', $brand->id)` and
  `$request->attributes->set('brand', $brand)` (lines 47-48). This is
  the single source of truth for "which brand is active"; it has no
  awareness of the switch-country modal at all.
- `app/Http/Controllers/Web/NewOrderController.php::create()` (and the
  sibling `renewal`/`gift`/`trial`/`coupon_redemption` controllers)
  pulls `$brand` straight from that request attribute and hands it to
  the view: `'brand' => $request->attributes->get('brand')` (line 35),
  which is what the Blade partial's `@if($brand->id === 4)` check uses.
- `Brand` model (`app/Models/Brand.php`) carries a `currency` column
  (alpha-3, e.g. `AUD`/`USD`) as a plain attribute — no per-request
  override mechanism exists on the model.
- `app/Services/OrderService.php` line 283 uses that brand's currency
  directly when materializing a `Plan` for an order:
  `'currency' => strtolower($brand->currency),` inside the
  `Plan::firstOrCreate([...brand_id=>$brand->id...])` call (lines
  259-284), and the subsequent `Order::create([...'brand_id' =>
$brand->id...])` (lines 287-299+) ties the order itself to that same
  brand.
- Brand seed data (`database/seeders/BrandSeeder.php`) confirms brand
  id 4 = `MOL_US` / `currency: USD` / `domain: localhost:8004`
  (lines 70-87) vs. brand id 1 = `MOL_AU` / `currency: AUD` / `domain:
localhost:8001` (lines 16-33). The AU link in the modal
  (`https://www.mathsonline.com.au/purchase`) is a different domain
  that — on production — would resolve to the `MOL_AU` brand row via
  the same `InjectContext` host-matching logic, giving AUD pricing on
  that next page load.

**Net effect**: clicking "Switch to Australian curriculum" does not
call any API to reassign brand/currency for the current session. It
simply sends the browser to a different production domain; that new
domain's own request cycle re-runs `InjectContext`, which resolves a
different `Brand` (AU) purely from the new host, and _that_ is what
changes displayed currency/pricing on the next page. The modal itself
has no direct line into pricing — it is a UX layer sitting entirely on
top of the existing host-based brand-resolution mechanism, not an
alternative or supplementary one.

## 5. JS backing this component

No dedicated JS module exists for this feature. `grep -rn
"switchCountry|geoip|USCurriculum" resources/js resources/views` finds
matches **only inside the Blade file itself** — there is no
`resources/js/**` file, no Alpine/Vue component, and no other
`<script>` block anywhere referencing this modal. All interactivity
(jQuery `$(document).ready`, the GeoIP AJAX call, `modal.show()` /
`modal.hide()`, the `#USCurriculum` click handler) is the one inline
`<script defer>` block at the bottom of
`switch_country_modal.blade.php` (lines 37-55), using the global
jQuery (`$`) already loaded by the surrounding Blade layout.

## Key files referenced

- `resources/views/orders/components/switch_country_modal.blade.php` — the modal itself (markup + inline JS)
- `resources/views/orders/create/{new,gift,coupon_redemption,renewal,trial_mode_1,trial_mode_2}.blade.php` — inclusion points
- `app/Http/Controllers/Api/GeoIpController.php` — GeoIP lookup endpoint (`GET /api/v1/geoip`, route name `api.v1.geoip.show`)
- `app/Services/LocationService.php` + `app/Services/Contracts/LocationServiceInterface.php` — calls `https://api.country.is/{ip}`
- `routes/api.php` (line 40-41) — route registration, no brand-context middleware applied
- `app/Http/Middleware/InjectContext.php` — host-based `Brand` resolution (the actual mechanism controlling currency/brand)
- `app/Http/Controllers/Web/NewOrderController.php` (and sibling order controllers) — passes `$request->attributes->get('brand')` into the view
- `app/Models/Brand.php` — `currency`, `domain`, `market` etc. as plain model attributes
- `database/seeders/BrandSeeder.php` — brand id 4 = `MOL_US`/USD/`localhost:8004`; brand id 1 = `MOL_AU`/AUD/`localhost:8001`
- `app/Services/OrderService.php` (line 283, and `Plan::firstOrCreate`/`Order::create` around lines 259-299) — where `$brand->currency` and `$brand->id` flow into `Plan`/`Order` creation

## Open questions / not found

- No mechanism was found for switching brand/currency **without** a
  full cross-domain navigation (e.g. no cookie, no query-param-driven
  brand override) — confirming brand/currency selection is host-only
  in this codebase, exactly as `InjectContext` implements it.
- The US "continue" button does not analytics-track or log the
  user's choice anywhere in this file or its includes.
