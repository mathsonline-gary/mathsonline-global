import type { components } from "@workspace/api-client";

/**
 * A brand's identity on the wire — membership's own `brands.code`, upper-case.
 *
 * The description is the authority on which codes exist, so this is its generated type rather than
 * a hand-written union: adding a brand to `/api/v2` is what makes it reachable here.
 *
 * Not what the URL carries. A purchase URL's first segment is a market slug (`au`), mapped onto a
 * code by `brand-code.ts` — see `../../../CONTEXT.md`.
 */
export type BrandCode = components["schemas"]["BrandCode"];

/**
 * One brand's public configuration — everything the purchase flows need to render and submit.
 *
 * Every field is on the description's allowlist. No secret is: not the Stripe secret or webhook
 * secret, the reCAPTCHA secret, the nonce secret, the Keap account key.
 *
 * The wire groups the publishable third-party keys under `stripe` and `google`. That grouping is
 * the payload's, not this application's, so it is flattened at the boundary along with the case.
 */
export type Brand = {
  code: BrandCode;
  /** The product name the customer sees this brand under. Copy interpolates it — it varies. */
  name: string;
  /** The country this brand sells into, as a display string. Not a routing key. */
  market: string;
  /** ISO 4217 alpha-3. Money is never redenominated here — this only picks the symbol. */
  currency: string;
  /** The brand's marketing site, whose purchase links point back at this application. */
  marketingWebsite: string;
  infoEmail: string;
  feedbackEmail: string;
  supportPhone: string | null;
  socialFacebook: string | null;
  socialInstagram: string | null;
  /** Publishable half only — this one is meant to reach the browser. */
  stripePublishableKey: string | null;
  /** Site half only. Domain-restricted per brand. */
  googleRecaptchaSiteKey: string | null;
  googleMapsApiKey: string | null;
  googleTagManagerContainerId: string | null;
};
