import { type components, unwrap } from "@workspace/api-client";

import { api, nextCache } from "@/lib/api/client";
import type { BrandCode } from "@/lib/brands/types";

import type { Pricing, PricingTable } from "./types";

/**
 * The API boundary for pricing — `GET /brands/{brandCode}/pricing`.
 *
 * Server-side only: prices decide what renders. No React `cache()` unlike `getBrand` — a query
 * object would be keyed by reference and so never dedupe, and one page makes one call. Next's
 * fetch cache still spans requests and users, tagged per brand.
 */

/** How long one brand's pricing may be stale. */
const PRICING_TTL_SECONDS = 300;

/**
 * Which pricings fill the table — never whether there is one. Resolved first-match: the renewal
 * coupon's campaign, then the promotion's, then the brand's default; `homeschool` picks the
 * homeschool pricings out of whichever won.
 *
 * Every code fails soft. Unknown, expired, spent and another brand's all give the brand's default
 * with the matching result field `null` — read `promotionCode` / `renewalCouponCode` to find out
 * what applied. None of this throws.
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
 * Throws rather than returning null, unlike `getBrand`: the only 404 here is a brand that does not
 * exist, and every caller has already resolved one through `requireBrand`.
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
