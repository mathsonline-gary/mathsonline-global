import { cache } from "react";

import { ApiError, type components, unwrap } from "@workspace/api-client";

import { api, nextCache } from "@/lib/api/client";

import type { Market, MarketCode } from "./types";

/**
 * The API boundary for market configuration — `GET /markets/{marketCode}`.
 *
 * Server-side only: the browser never calls the membership API. Market resolution gates a 404, so
 * it has to happen before anything renders, and a client-side cache could not do that — content
 * would flash and then soft-404. Server `cache()` plus `revalidateTag` also beats a client cache on
 * its own terms: one payload for every user, invalidatable from one place.
 */

/** How long one market's configuration may be stale. */
const MARKET_TTL_SECONDS = 300;

/** The wire is `snake_case` and grouped; this application is neither. Mapping happens only here. */
function toMarket(payload: components["schemas"]["Market"]): Market {
  return {
    code: payload.code,
    country: payload.name,
    currency: payload.currency,
    marketingWebsite: payload.marketing_website,
    infoEmail: payload.info_email,
    feedbackEmail: payload.feedback_email,
    supportPhone: payload.support_phone ?? null,
    socialFacebook: payload.social_facebook ?? null,
    socialInstagram: payload.social_instagram ?? null,
    stripePublishableKey: payload.stripe?.publishable_key ?? null,
    googleRecaptchaSiteKey: payload.google?.recaptcha_site_key ?? null,
    googleMapsApiKey: payload.google?.maps_api_key ?? null,
    googleTagManagerContainerId:
      payload.google?.tag_manager_container_id ?? null,
  };
}

/**
 * One market's configuration, or null when the API does not serve that code.
 *
 * Every `[market]` page resolves through this, so it is cached twice over, doing different jobs.
 * React's `cache()` dedupes within one request, so a layout and the page beneath it cost one fetch.
 * The `next` tag spans requests and users, and is what `revalidateTag` reaches — tagged per market
 * so that invalidating one does not cost the others their cache.
 *
 * A 404 is the answer "no such market", so it returns null and lets `requireMarket` turn that into
 * the 404 page. Anything else throws: the API being unreachable means the purchase page 500s, which
 * is already true of anything priced.
 */
export const getMarket = cache(
  async (code: MarketCode): Promise<Market | null> => {
    try {
      const { data } = unwrap(
        await api().GET("/markets/{marketCode}", {
          params: { path: { marketCode: code } },
          ...nextCache({
            revalidate: MARKET_TTL_SECONDS,
            tags: [`market:${code}`],
          }),
        }),
      );

      return toMarket(data);
    } catch (error) {
      if (error instanceof ApiError && error.isNotFound) return null;

      throw error;
    }
  },
);
