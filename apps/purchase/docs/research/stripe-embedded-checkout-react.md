# Stripe Embedded Checkout: React / Next.js App Router Integration Patterns

> **Where this came from.** Research carried out in the `mathsonline-gary/purchase-web`
> repository, before this application moved into this monorepo. Its findings are unedited, including its
> links to that repository's issues (only the formatting is normalised to this repository's), because
> what it is worth is the _findings_ — what membership actually
> does today — and rewriting those to match later decisions would falsify them.
>
> It is **not a specification.** `packages/openapi-v2` is the only authority on the `/api/v2`
> contract. Where this document describes an endpoint, a field or an auth scheme the description does
> not have, the description wins and the difference is deliberate.

Research for a future prototype ticket that replaces `membership`'s vanilla-JS
`stripe.initEmbeddedCheckout()` Blade view with a React implementation in
`purchase-web`. This document is background reading only — no code changes.

## TL;DR

- Stripe's official React binding for Embedded Checkout is `@stripe/react-stripe-js`'s
  `<EmbeddedCheckoutProvider>` + `<EmbeddedCheckout>` components (distinct from
  `<Elements>`/custom Elements UI and from hosted-Checkout redirects). They wrap the
  same underlying `stripe.createEmbeddedCheckoutPage()`/`initEmbeddedCheckout()` JS API
  that `membership`'s Blade view calls directly, but manage mount/unmount via React
  component lifecycle instead of imperative `.mount()`/`.destroy()` calls.
- Session creation must happen server-side (needs the Stripe **secret** key). Stripe's
  own Next.js examples create the session in a Route Handler or a Server Action; in
  this project's real architecture, `purchase-web` almost certainly won't create the
  session at all — it will `fetch()` a `client_secret` from the `membership` Laravel
  API's existing session-creation endpoint (the same one `OrderCheckoutController`
  already populates via `StripeClient::createCheckoutSession()`).
- `'use client'` is required only on the component that renders
  `EmbeddedCheckoutProvider`/`EmbeddedCheckout` (browser APIs, React context, effects).
  The page itself can stay a Server Component that just renders that client child; a
  Route Handler or Server Action used for session creation is server-only code and
  never needs `'use client'`.
- The client's "checkout complete" UI state (via `onComplete`, or a `return_url` +
  session-status check) is explicitly documented by Stripe as **UX-only** — fulfillment
  must be driven by the `checkout.session.completed` webhook (or an authoritative
  session-status poll against the Sessions API), which is exactly what `membership`
  already does server-side today.
- Current versions (npm registry, checked 2026-07-24): `@stripe/react-stripe-js@6.8.0`
  (peer deps `@stripe/stripe-js` `>=9.5.0 <10.0.0`, React/ReactDOM `>=16.8.0 <20.0.0`,
  dual ESM+CJS), `@stripe/stripe-js@9.12.0` (dual ESM+CJS), `stripe@22.3.2` Node SDK
  (CJS, requires Node >=18).

---

## Current `membership` implementation (recap, for comparison)

Read from `/Users/gary/GitHub/membership/resources/views/orders/checkout/show.blade.php`
and `/Users/gary/GitHub/membership/app/Http/Controllers/Web/OrderCheckoutController.php`
(read-only background context; not modified).

- `OrderCheckoutController::show()` looks up the `Order` (with its `stripeTransaction`
  relation) server-side and passes `clientSecret: $order->stripeTransaction->checkout_session_client_secret`
  directly into the Blade view — the Checkout Session itself was already created earlier
  in the order flow by `App\Services\Integrations\Stripe\StripeClient::createCheckoutSession()`
  (calls `$this->client->checkout->sessions->create($payload)` and persists
  `checkout_session_id` / `checkout_session_client_secret` on the order's Stripe
  transaction row). So session creation is fully decoupled from page render — by the
  time this view renders, the secret already exists in the DB.
- The Blade view (`orders.checkout.show`) renders a bare `<div id="checkout">` when
  `$order->status === OrderStatus::READY`, and a `@section('scripts')` block loads
  `https://js.stripe.com/v3/`, constructs `Stripe(publishableKey)`, and calls:
  ```js
  const checkout = await stripe.initEmbeddedCheckout({
    clientSecret: "{{ $clientSecret }}",
    onComplete: function () {
      checkout.destroy(); // manual cleanup, imperative
      window.location.href =
        '{{ route("orders.checkout.success") }}' + "?oid=" + "{{ $orderId }}";
      return false;
    },
  });
  checkout.mount("#checkout"); // manual mount, imperative
  ```
- There is no client-side `fetchClientSecret` callback in this implementation — the
  secret is baked into the server-rendered HTML (Blade interpolation), not fetched via
  an API call from the browser. This is the deprecated/legacy `clientSecret` option
  path rather than the now-recommended `fetchClientSecret` callback path (see §1/§5).
- Success page (`OrderCheckoutController::success()`) explicitly notes: _"We allow it to
  show the success page for unpaid orders, because there is a delay of order status to
  be updated handled by the webhooks"_ — i.e., `membership` already treats the redirect/
  success page as UX-only and the webhook as the fulfillment source of truth. This
  matches Stripe's documented guidance (§4) and should carry over unchanged into the
  React implementation's mental model.
- Webhook handling itself lives in `App\Services\StripeEventService` (referenced by
  grep in `app/Services/StripeEventService.php`), out of scope for `purchase-web`.

---

## 1. Where checkout-session creation should happen (Next.js App Router)

Stripe's official examples create the Checkout Session **server-side**, because doing
so requires the Stripe secret key, which must never reach the browser. In a Next.js
App Router co-located example, Stripe shows this as a **Route Handler**:

```ts
// app/api/checkout_sessions/route.ts
const session = await stripe.checkout.sessions.create({
  line_items: [{ price: "{{PRICE_ID}}", quantity: 1 }],
  mode: "{{CHECKOUT_MODE}}",
  success_url: `${origin}/success?session_id={CHECKOUT_SESSION_ID}`,
  automatic_tax: { enabled: true },
});
return NextResponse.redirect(session.url, 303);
```

Source: https://docs.stripe.com/checkout/quickstart (Next.js client tab; fetched
2026-07-24). Note this particular quickstart snippet is for **hosted/redirect**
Checkout (`session.url` + redirect), but the "create the session server-side, in a
Route Handler" pattern is the same one Stripe reuses for embedded mode.

For **embedded** Checkout specifically, Stripe's Embedded Checkout quickstart shows
the session-creation endpoint as a generic server endpoint that sets
`ui_mode: 'embedded_page'` and returns `{ clientSecret: session.client_secret }`
instead of redirecting:

```js
app.post("/create-checkout-session", async (req, res) => {
  const session = await stripe.checkout.sessions.create({
    ui_mode: "embedded_page",
    line_items: [{ price: "{{PRICE_ID}}", quantity: 1 }],
    mode: "payment",
    return_url: `${YOUR_DOMAIN}/return?session_id={CHECKOUT_SESSION_ID}`,
  });
  res.send({ clientSecret: session.client_secret });
});
```

Source: https://docs.stripe.com/checkout/embedded/quickstart (fetched 2026-07-24).
The same doc also shows this as a **Next.js Server Action** returning
`{ clientSecret: session.client_secret }` directly to a client component, i.e. Stripe
documents _both_ a Route Handler and a Server Action as valid — it doesn't mandate one.

**Implication for this project's real architecture:** `purchase-web` will not create
the Checkout Session at all. That responsibility already lives in `membership`
(`StripeClient::createCheckoutSession()`, confirmed in Step 0 above) and will continue
to live there — `membership` holds the Stripe secret key and the order/pricing domain
logic. The prototype ticket's job is narrower than Stripe's quickstarts assume: instead
of a Route Handler/Server Action that calls `stripe.checkout.sessions.create(...)`
directly, `purchase-web`'s `fetchClientSecret` (see §2) — or a thin Route Handler that
proxies to it — should call the existing `membership` API endpoint that already returns
`checkout_session_client_secret`, and simply forward that string to
`EmbeddedCheckoutProvider`. Whether that fetch happens directly from the browser to
`membership`'s API, or via a `purchase-web` Route Handler acting as a same-origin proxy
(useful for CORS/auth-cookie handling), is an implementation decision for the later
prototype ticket, not something Stripe's docs prescribe either way.

## 2. The client-side embedding pattern

Yes — `@stripe/react-stripe-js` ships dedicated components for Embedded Checkout,
distinct from both the `Elements`/`PaymentElement` custom-UI components and from a
plain redirect-to-Checkout link:

```tsx
import { loadStripe } from "@stripe/stripe-js";
import {
  EmbeddedCheckoutProvider,
  EmbeddedCheckout,
} from "@stripe/react-stripe-js";

const stripePromise = loadStripe("pk_test_...");

export function CheckoutPage() {
  const fetchClientSecret = useCallback(() => {
    return fetch("/create-checkout-session", { method: "POST" })
      .then((res) => res.json())
      .then((data) => data.clientSecret);
  }, []);

  return (
    <EmbeddedCheckoutProvider
      stripe={stripePromise}
      options={{ fetchClientSecret }}
    >
      <EmbeddedCheckout />
    </EmbeddedCheckoutProvider>
  );
}
```

Sources:

- context7 `/stripe/react-stripe-js`, query "EmbeddedCheckoutProvider EmbeddedCheckout
  component fetchClientSecret stripePromise loadStripe usage example"
  (https://github.com/stripe/react-stripe-js/blob/master/_autodocs/api-reference/embedded-checkout.md
  and `.../_autodocs/configuration.md`)
- https://docs.stripe.com/checkout/embedded/quickstart (React tab, fetched 2026-07-24)

**Component API** (from the same context7 query):

- `EmbeddedCheckoutProvider` props: `stripe` (a `Stripe` instance or a Promise resolving
  to one, from `loadStripe`) — **required**; `options.clientSecret` (string, static,
  now considered the older/deprecated path) **or** `options.fetchClientSecret`
  (`() => Promise<string>`, the recommended path since it lets Checkout start loading
  before the secret resolves); `options.onComplete`; `options.onShippingDetailsChange`;
  `options.onLineItemsChange`.
- `EmbeddedCheckout` props: just `id` and `className` for the mount container — it
  renders the Checkout iframe wherever it's placed in the tree.

**`fetchClientSecret` vs the deprecated `clientSecret`:** the plain JS reference
(`stripe.createEmbeddedCheckoutPage`) explicitly documents `clientSecret` as
"**Deprecated** in favor of `fetchClientSecret`, which offers a faster loading
experience" — source: context7 `/websites/stripe_js`, query "Embedded Checkout Next.js
App Router create checkout session route handler client_secret return_url session
status" (https://docs.stripe.com/js/embedded_checkout, https://docs.stripe.com/js/custom_checkout/get_contact_details_element).
This matters directly for the migration: `membership`'s current Blade template uses the
static `clientSecret` form (`clientSecret: '{{ $clientSecret }}'`, baked in at render
time), so the React port is an opportunity to move to `fetchClientSecret` and have the
component itself own the fetch to `membership`'s API.

**Mechanical difference vs vanilla JS:** the vanilla pattern is fully imperative —
`await stripe.initEmbeddedCheckout({...})` returns a `checkout` object, then you call
`checkout.mount(selector)` yourself and must remember to call `checkout.destroy()`
(exactly what `membership`'s Blade `onComplete` callback does manually today). The
React components fold all of that into component lifecycle: mounting the
`<EmbeddedCheckout>` element mounts Checkout, and unmounting it (e.g. navigating away,
or the parent conditionally rendering something else) triggers cleanup automatically —
there is no manual `.mount()`/`.destroy()` call for application code to get right or
forget. `onComplete` is still a callback option, but it's just for reacting to
completion (e.g. triggering a route change), not for lifecycle teardown.

## 3. Server vs Client Component considerations (App Router)

- `EmbeddedCheckoutProvider` and `EmbeddedCheckout` **must** be used inside a
  `'use client'` module. They rely on browser globals (`Stripe.js` loads and mounts an
  iframe into the DOM), React context (the Provider), and effects to manage the
  Checkout instance's lifecycle — none of which can run in a Server Component render.
  This isn't a Stripe-specific rule so much as a direct consequence of what these
  components do (DOM mounting, `useEffect`-driven mount/unmount, browser-only
  `loadStripe`/`Stripe.js` script), which is exactly the class of component App Router
  requires `'use client'` for.
- The **page** that hosts the checkout does not itself need `'use client'` — it can be
  a Server Component that fetches whatever order/brand context it needs server-side
  (mirroring what `OrderCheckoutController::show()` does today: look up the order,
  check status, pick brand) and then renders a small client child component
  (e.g. `<EmbeddedCheckoutClient orderId=... />`) that contains the
  `EmbeddedCheckoutProvider`/`EmbeddedCheckout` pair. This mirrors the community
  Next.js pattern surfaced via WebSearch (multiple third-party App Router walkthroughs,
  e.g. https://medium.com/@josh.ferriday/intergrating-stripe-payments-with-next-app-router-9e9ba130f101
  and https://dev.to/thatanjan/accepting-payments-with-stripe-hosted-and-embedded-checkout-in-nextjs-2jm2 —
  cited as secondary/community sources, not official Stripe docs, since Stripe's own
  README/reference doesn't spell out the Server/Client Component split explicitly): a
  Server Action or Route Handler (marked `'use server'` or living under `app/api/.../route.ts`)
  creates/proxies the session server-side, and only the small leaf component that
  renders the Provider is `'use client'`.
- Any session-creation code — a Route Handler, a Server Action, or (per this project's
  actual architecture, §1) a server-side `fetch` to `membership`'s API from a Route
  Handler acting as a proxy — runs entirely server-side and never needs `'use client'`;
  Stripe's secret key must never be referenced from client code in any case.
- `@stripe/react-stripe-js`'s own README states only a React-version floor ("the
  minimum supported version of React is v16.8") and gives no explicit Next.js/RSC
  guidance (confirmed via WebFetch of
  https://github.com/stripe/react-stripe-js/blob/master/README.md, fetched 2026-07-24)
  — the Server/Client Component split above is an inference from React Server
  Component rules generally, not a documented Stripe statement.

## 4. Webhook handling: the client/server confirmation contract

Stripe's Embedded Checkout guide is explicit that the redirect/`return_url` +
client-side status check is **UX-only**, and webhooks are the source of truth for
fulfillment:

> **Fulfill orders** — Set up a webhook to fulfil orders after a payment succeeds.
> Webhooks are the most reliable way to handle business-critical events.

Source: https://docs.stripe.com/checkout/embedded/quickstart, linking to
https://docs.stripe.com/checkout/fulfillment (fetched 2026-07-24).

The documented flow for the "did it work" UI question is:

1. On completion, either `onComplete` fires (for `redirect_on_completion: 'if_required'`
   sessions that don't navigate away), or Stripe redirects to `return_url` with
   `?session_id={CHECKOUT_SESSION_ID}`.
2. The client (or a server endpoint it calls) does `stripe.checkout.sessions.retrieve(session_id)`
   and reads `session.status`, which is one of `open | complete | expired` — source:
   context7 `/stripe/stripe-node`, query "checkout session status webhook
   checkout.session.completed fulfillment source of truth"
   (`https://github.com/stripe/stripe-node/blob/master/src/resources/Checkout/Sessions.ts`).
   `status === 'complete'` → show success UI; `status === 'open'` → remount/retry.
3. Separately and asynchronously, Stripe sends a `checkout.session.completed` webhook
   event (type defined in `stripe-node`'s `Events.ts`, same context7 query) — this is
   what actually triggers fulfillment server-side, and it can arrive before, during, or
   after the client-side redirect/poll.

**What the new React frontend needs to internalize:** the `EmbeddedCheckout`
component's `onComplete` callback, and any `return_url`/session-status check the
`purchase-web` "success" page performs, are for **display purposes only** — "payment
looks done, show a nice message" — not proof that the order has been fulfilled.
`membership` already encodes this correctly today: its `OrderCheckoutController::success()`
comment explicitly says it shows the success page for unpaid orders too, "because
there is a delay of order status to be updated handled by the webhooks." The React
port should preserve that same contract — `purchase-web` reacts to `onComplete`/session
status for UI, but the actual order/membership state change remains driven entirely by
`membership`'s existing webhook handler (`StripeEventService`), which is out of scope
to change here.

## 5. Package/dependency names and current versions (checked 2026-07-24, npm registry)

| Package                   | Latest version | Module format                                                                                                                                                                                                                                                                             | Notes                                                                                                                                                                                                                                                            |
| ------------------------- | -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `@stripe/react-stripe-js` | **6.8.0**      | Dual ESM (`dist/react-stripe.esm.mjs`) + CJS (`dist/react-stripe.js`), plus a separate `./checkout` subpath export (`@stripe/react-stripe-js/checkout`, for the newer `CheckoutFormProvider`/`CheckoutForm` custom-checkout components — a different API from `EmbeddedCheckoutProvider`) | peerDependencies: `@stripe/stripe-js` `>=9.5.0 <10.0.0`, `react` `>=16.8.0 <20.0.0`, `react-dom` `>=16.8.0 <20.0.0`. So React 19 (Next.js 16 default) is within range.                                                                                           |
| `@stripe/stripe-js`       | **9.12.0**     | Dual ESM (`lib/index.mjs`) + CJS (`lib/index.js`)                                                                                                                                                                                                                                         | This is the `loadStripe` wrapper; loads the actual `js.stripe.com/v3` script under the hood.                                                                                                                                                                     |
| `stripe` (Node SDK)       | **22.3.2**     | CJS only (`cjs/stripe.cjs.node.js`; no `module`/ESM entry point in `package.json`)                                                                                                                                                                                                        | `engines.node: >=18`. This is the server-side SDK `membership`'s `StripeClient` uses (via PHP `stripe-php` on that side, not this Node package — but relevant if `purchase-web` ever adds its own Route Handler that calls Stripe server-side, e.g. as a proxy). |

Versions were retrieved directly from the npm registry (`registry.npmjs.org/<pkg>/latest`)
since `npmjs.com` package pages returned HTTP 403 to WebFetch. Peer-dependency ranges
and export maps are read from each package's actual `package.json` at that endpoint —
not from context7 or docs pages, which do not reliably surface exact version numbers.

Given peer ranges above, adding `@stripe/react-stripe-js` + `@stripe/stripe-js` to
`purchase-web` (Next.js 16 / React 19, per this repo's `CLAUDE.md`) is compatible with
current peer dependency constraints.

---

## References / sources

**Official Stripe docs (WebFetch, fetched 2026-07-24):**

- https://docs.stripe.com/checkout/embedded/quickstart — Embedded Checkout quickstart:
  server-side session creation (Node/Express + Next.js Server Action variants), React
  `EmbeddedCheckoutProvider`/`EmbeddedCheckout` sample, vanilla JS
  `createEmbeddedCheckoutPage` sample, return-URL/session-status pattern, webhook
  fulfillment guidance.
- https://docs.stripe.com/checkout/quickstart — Next.js quickstart for hosted/redirect
  Checkout, showing the Route Handler (`app/api/checkout_sessions/route.ts`) pattern
  for server-side session creation (used here for the general "create sessions
  server-side in a Route Handler" pattern, not embedded mode specifically).
- https://github.com/stripe/react-stripe-js/blob/master/README.md — confirms React
  16.8 minimum, no explicit Next.js/App Router statement.

**context7 MCP queries:**

- Library `/stripe/react-stripe-js` ("React Stripe.js"), query: "EmbeddedCheckoutProvider
  EmbeddedCheckout component fetchClientSecret stripePromise loadStripe usage example" →
  surfaced `_autodocs/api-reference/embedded-checkout.md` and `_autodocs/configuration.md`
  content (component props, usage examples).
- Library `/websites/stripe_js` ("Stripe.js" docs-site crawl), query: "Embedded Checkout
  Next.js App Router create checkout session route handler client_secret return_url
  session status" → surfaced `docs.stripe.com/js/embedded_checkout` (`createEmbeddedCheckoutPage`
  reference, deprecation note on `clientSecret` vs `fetchClientSecret`).
- Library `/stripe/stripe-node` ("Stripe Node"), query: "checkout session status webhook
  checkout.session.completed fulfillment source of truth" → surfaced
  `stripe-node/src/resources/Checkout/Sessions.ts` (`status` field, `retrieve()`) and
  `stripe-node/src/resources/Events.ts` (`CheckoutSessionCompletedEvent`).
- Library `/websites/stripe` ("Stripe" docs-site crawl), query: "Next.js App Router build
  a checkout page with Next.js embedded checkout server component route handler create
  session" → surfaced `docs.stripe.com/checkout/quickstart` and
  `docs.stripe.com/billing/subscriptions/build-subscriptions` snippets (`ui_mode:
'embedded_page'`, `return_url` with `{CHECKOUT_SESSION_ID}`).

**npm registry (Bash/curl, fetched 2026-07-24):**

- `https://registry.npmjs.org/@stripe/react-stripe-js/latest`
- `https://registry.npmjs.org/@stripe/stripe-js/latest`
- `https://registry.npmjs.org/stripe/latest`
  (npmjs.com HTML package pages returned HTTP 403 to WebFetch; registry API used instead.)

**Secondary/community sources (WebSearch, not official Stripe docs — cited only for the
Server/Client Component split in §3, which Stripe's own docs don't spell out explicitly):**

- https://medium.com/@josh.ferriday/intergrating-stripe-payments-with-next-app-router-9e9ba130f101
- https://dev.to/thatanjan/accepting-payments-with-stripe-hosted-and-embedded-checkout-in-nextjs-2jm2

**membership repo (read-only background context, Step 0):**

- `/Users/gary/GitHub/membership/resources/views/orders/checkout/show.blade.php`
- `/Users/gary/GitHub/membership/app/Http/Controllers/Web/OrderCheckoutController.php`
- `/Users/gary/GitHub/membership/app/Services/Integrations/Stripe/StripeClient.php`
  (referenced via grep for `createCheckoutSession`; not fully read, only the relevant
  `checkout.sessions.create` / `checkout_session_client_secret` lines)
