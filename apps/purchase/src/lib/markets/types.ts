import type { components } from "@workspace/api-client";

/**
 * A market's identity on the wire and in the URL. Lower-case, stable, and not an ISO country code
 * — `uk` is not ISO 3166-1 alpha-2 (`GB` is).
 *
 * The description is the authority on which codes exist, so this is its generated type rather than
 * a hand-written union: adding a market to `/api/v2` is what makes it routable here.
 */
export type MarketCode = components["schemas"]["MarketCode"];

/**
 * One market's public configuration — everything the purchase flows need to render and submit.
 *
 * Every field is on the description's allowlist. No secret is: not the Stripe secret or webhook
 * secret, the reCAPTCHA secret, the nonce secret, the Keap account key.
 *
 * There is no `name`. The description's `Market.name` is the *country* ("Australia"), while
 * membership's `brands.name` is the *brand* ("MathsOnline") — the same field name for two
 * different things. Binding it to `country` here means no call site can confuse them, and the
 * brand name is a literal in copy, because there is one brand.
 *
 * The wire groups the publishable third-party keys under `stripe` and `google`. That grouping is
 * the payload's, not this application's, so it is flattened at the boundary along with the case.
 */
export type Market = {
  code: MarketCode;
  /** The country this market sells into, as a display string. Not a routing key. */
  country: string;
  /** ISO 4217 alpha-3. Money is never redenominated here — this only picks the symbol. */
  currency: string;
  /** The market's marketing site, whose purchase links point back at this application. */
  marketingWebsite: string;
  infoEmail: string;
  feedbackEmail: string;
  supportPhone: string | null;
  socialFacebook: string | null;
  socialInstagram: string | null;
  /** Publishable half only — this one is meant to reach the browser. */
  stripePublishableKey: string | null;
  /** Site half only. Domain-restricted per market. */
  googleRecaptchaSiteKey: string | null;
  googleMapsApiKey: string | null;
  googleTagManagerContainerId: string | null;
};
