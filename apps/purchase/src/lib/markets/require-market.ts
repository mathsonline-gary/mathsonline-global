import { notFound } from "next/navigation";

import { isMarketCode } from "./market-code";
import { getMarket } from "./markets.api";
import type { Market } from "./types";

/**
 * The market for a `[market]` route, or a 404.
 *
 * The market comes from the first path segment via a plain dynamic segment — no middleware rewrite
 * and no GeoIP. An unknown code is a hard 404 rather than a redirect or a guess from the visitor's
 * location, because a purchase page has to be about the market the URL names or about nothing.
 *
 * The membership check runs first, so a segment that cannot name a market never reaches the API.
 *
 * Kept apart from `markets.api` so that module stays a pure data boundary and this one owns the
 * routing consequence. Safe to call from a layout and the pages beneath it — `getMarket` is
 * request-cached, so repeat calls for the same market cost one fetch.
 */
export async function requireMarket(segment: string): Promise<Market> {
  if (!isMarketCode(segment)) {
    notFound();
  }

  const market = await getMarket(segment);

  if (!market) {
    notFound();
  }

  return market;
}
