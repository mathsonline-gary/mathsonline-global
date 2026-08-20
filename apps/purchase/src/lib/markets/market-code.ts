import type { MarketCode } from "./types";

/**
 * Exhaustive in both directions on purpose. A `Record` keyed by `MarketCode` fails to compile when
 * the description gains a market this list is missing, and when it drops one this list still has —
 * so the two cannot drift apart silently.
 */
const MARKET_CODES: Record<MarketCode, true> = { au: true, us: true, uk: true };

/**
 * Whether a path segment names a market this application serves.
 *
 * A membership check, not a shape check: the description enumerates the markets, so an unserved
 * code is knowable without asking the API. That keeps a crawler's guess at `/favicon.ico` or `/de`
 * from costing a network request, and it narrows `string` to `MarketCode` — which the typed client
 * requires before it will accept the segment as a path parameter.
 *
 * Matching is exact and lower-case. `/AU` is a miss, not a redirect to `/au`.
 *
 * `Object.hasOwn` rather than `in`, which would also answer true for `toString`.
 */
export function isMarketCode(value: string): value is MarketCode {
  return Object.hasOwn(MARKET_CODES, value);
}
