import type { BrandCode } from "./types";

/**
 * The market slug a purchase URL carries, mapped onto the brand it names.
 *
 * A decision, not a derivation: more than one brand can serve one country — `MOL_US` and `CTC_US`
 * both sell into the United States — so no rule turns `us` into a code.
 *
 * `satisfies` checks one direction: drop a code from the enum and this stops compiling.
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
 * A membership check, not a shape check: the slugs are known here, so a crawler's guess at
 * `/favicon.ico` or `/de` costs no network request, and it produces the `BrandCode` the typed
 * client requires as a path parameter.
 *
 * Matching is exact and lower-case — `/AU` is a miss, not a redirect. `Object.hasOwn` rather than
 * `in`, which would also answer true for `toString`.
 */
export function brandCodeForMarketSlug(segment: string): BrandCode | undefined {
  return Object.hasOwn(BRAND_CODE_BY_MARKET_SLUG, segment)
    ? BRAND_CODE_BY_MARKET_SLUG[segment as MarketSlug]
    : undefined;
}
