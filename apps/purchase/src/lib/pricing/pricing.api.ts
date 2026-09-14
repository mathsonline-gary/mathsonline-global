import { type components, unwrap } from "@workspace/api-client";

import { api, nextCache } from "@/lib/api/client";
import type { BrandCode } from "@/lib/brands/types";

import type { Pricing, PricingTable } from "./types";

/**
 * The API boundary for pricing — `GET /brands/{brandCode}/pricing`.
 *
 * Server-side only, like the brand read: prices decide what renders, so they have to be resolved
 * before the page is sent rather than filled in afterwards.
 *
 * No React `cache()` here, unlike `getBrand`. A brand read is keyed by one string and happens in a
 * layout and the page beneath it; a pricing read takes a query object, which `cache()` would key by
 * reference and so never dedupe, and one page makes one call. Next's fetch cache still spans
 * requests and users, tagged per brand.
 */

/** How long one brand's pricing may be stale. */
const PRICING_TTL_SECONDS = 300;

/**
 * Which pricings fill the table — never whether there is one. The API resolves these first-match:
 * the renewal coupon's campaign, then the promotion's, then the brand's default. `homeschool` then
 * picks the homeschool pricings out of whichever campaign won.
 *
 * Every code fails soft. Unknown, expired, spent or another brand's are all the same answer — the
 * brand's default pricing, with the corresponding field on the result `null`. So read the result's
 * `promotionCode` / `renewalCouponCode` to find out what applied; none of this throws.
 */
export type PricingQuery = {
  promotionCode?: string;
  /** Takes precedence over `promotionCode`. */
  renewalCouponCode?: string;
  homeschool?: boolean;
  /** Honoured only for a brand with testing pricings enabled, ignored otherwise. */
  testingToken?: string;
  /** Accompanies `promotionCode` for a promotion that only applies to a holder of a signed code. */
  nonceCode?: string;
};

/** The wire is `snake_case`; this application is not. Mapping happens only here. */
function toPricing(payload: components["schemas"]["Pricing"]): Pricing {
  return {
    code: payload.code,
    price: payload.price,
    priceOriginal: payload.price_original,
    priceSaved: payload.price_saved,
    currency: payload.currency,
    recurring: payload.recurring,
    studentLimit: payload.student_limit,
    billingPeriod: {
      interval: payload.billing_period.interval,
      count: payload.billing_period.count,
      extraCount: payload.billing_period.extra_count,
    },
    installmentCount: payload.installment_count,
  };
}

function toPricingTable(
  payload: components["schemas"]["PricingTable"],
): PricingTable {
  return {
    promotionCode: payload.promotion_code,
    renewalCouponCode: payload.renewal_coupon_code,
    single: payload.single.map(toPricing),
    family: payload.family.map(toPricing),
  };
}

/**
 * One brand's pricing table.
 *
 * Throws rather than returning null, which is the opposite of `getBrand` and deliberate: the only
 * 404 here is a brand that does not exist, and every caller has already resolved one through
 * `requireBrand`. A page that cannot price cannot render, so it 500s.
 */
export async function getPricing(
  code: BrandCode,
  query: PricingQuery = {},
): Promise<PricingTable> {
  const { data } = unwrap(
    await api().GET("/brands/{brandCode}/pricing", {
      params: {
        path: { brandCode: code },
        query: {
          promotion_code: query.promotionCode,
          renewal_coupon_code: query.renewalCouponCode,
          // Sent only when true: `homeschool=false` is the default and would split the cache.
          homeschool: query.homeschool || undefined,
          testing_token: query.testingToken,
          nonce_code: query.nonceCode,
        },
      },
      ...nextCache({
        revalidate: PRICING_TTL_SECONDS,
        tags: [`pricing:${code}`],
      }),
    }),
  );

  return toPricingTable(data);
}
