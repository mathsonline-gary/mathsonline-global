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
