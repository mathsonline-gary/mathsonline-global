import { createApiClient } from "@workspace/api-client";

/**
 * One client per request. Never hoist this to a module-level constant: on the server that would
 * share a request's credentials with the next request.
 */
export function api() {
  const baseUrl = process.env.API_URL;
  if (!baseUrl)
    throw new Error("API_URL is not set — it must point at the /api/v2 root.");

  return createApiClient({ baseUrl });
}

/**
 * Next.js cache options for one request, as an `openapi-fetch` init fragment:
 *
 * ```ts
 * api().GET("/brands/{brandCode}", {
 *   params: { path: { brandCode } },
 *   ...nextCache({ revalidate: 300, tags: [`brand:${brandCode}`] }),
 * });
 * ```
 *
 * Needed because `openapi-fetch` builds a `Request` and calls `fetch(request, ext)`, where `ext`
 * comes from the *client's* `requestInitExt`. A `next` key on a per-call init lands on the
 * `Request` instead, where Next's patched `fetch` never looks, so it would be silently ignored and
 * every read uncached. Overriding `fetch` for the call is what gets per-brand tags through.
 *
 * Here and not in `@workspace/api-client`, which holds no app-specific policy. Move it once a
 * second app needs it.
 */
export function nextCache(config: NextFetchRequestConfig) {
  return {
    fetch: (request: Request) => globalThis.fetch(request, { next: config }),
  };
}
