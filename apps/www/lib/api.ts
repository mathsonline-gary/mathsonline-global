import { createApiClient, unwrap } from "@workspace/openapi-v2/client";
import type { components } from "@workspace/openapi-v2/client";

export type Market = components["schemas"]["Market"];
export type MarketCode = components["schemas"]["MarketCode"];

/**
 * One client per request. Never hoist this to a module-level constant: on the server that would
 * share a request's credentials with the next request.
 */
function api() {
  const baseUrl = process.env.API_URL;
  if (!baseUrl) throw new Error("API_URL is not set — it must point at the /api/v2 root.");

  return createApiClient({ baseUrl });
}

/**
 * Storefront configuration for one market. Throws `ApiError` — 404 when the market is unknown.
 *
 * v2 wraps every payload in `{data: …}`; unwrapping that envelope is this layer's job, so callers
 * never see it.
 */
export async function getMarket(marketCode: MarketCode): Promise<Market> {
  const body = unwrap(
    await api().GET("/markets/{marketCode}", { params: { path: { marketCode } } }),
  );

  return body.data;
}
