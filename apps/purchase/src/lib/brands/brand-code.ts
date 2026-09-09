import type { BrandCode } from "./types";

/**
 * The market slug a purchase URL carries, mapped onto the brand it names.
 *
 * A decision, not a derivation. Membership's codes are its own (`MOL_AU`), and more than one brand
 * can serve one country — `MOL_US` and `CTC_US` both sell into the United States — so no rule turns
 * `us` into a code. This table is where that choice is written down.
 *
 * `satisfies` keeps it honest against the description in one direction: drop a code from the enum
 * and this stops compiling.
 *
 * ponytail: the other direction is unchecked — a brand added to the description with no slug here
 * is simply unreachable rather than a compile error. Add the inverse `Record<BrandCode, MarketSlug>`
 * if a brand ever ships unroutable.
 */
const BRAND_CODE_BY_MARKET_SLUG = {
  au: "MOL_AU",
  uk: "MOL_UK",
  us: "MOL_US",
} as const satisfies Record<string, BrandCode>;

type MarketSlug = keyof typeof BRAND_CODE_BY_MARKET_SLUG;

/**
 * The brand a path segment names, or `undefined` when it names none.
 *
 * A membership check, not a shape check: the slugs are known here, so an unserved segment is
 * knowable without asking the API. That keeps a crawler's guess at `/favicon.ico` or `/de` from
 * costing a network request, and it produces the `BrandCode` the typed client requires before it
 * will accept the segment as a path parameter.
 *
 * Matching is exact and lower-case. `/AU` is a miss, not a redirect to `/au`.
 *
 * `Object.hasOwn` rather than `in`, which would also answer true for `toString`.
 */
export function brandCodeForMarketSlug(segment: string): BrandCode | undefined {
  return Object.hasOwn(BRAND_CODE_BY_MARKET_SLUG, segment)
    ? BRAND_CODE_BY_MARKET_SLUG[segment as MarketSlug]
    : undefined;
}
