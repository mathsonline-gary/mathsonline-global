import createClient, { type Client, type Middleware } from "openapi-fetch";

import type { paths } from "../../dist/types";
import { ApiError } from "./errors";

export { ApiError } from "./errors";
export type { paths, components } from "../../dist/types";

export type ApiClient = Client<paths>;

export interface ApiClientConfig {
  /**
   * Root of the API, including the `/api/v2` prefix — the description's `servers[].url`. Per market
   * host, so it is the caller's to decide, never this package's.
   */
  baseUrl: string;
  /**
   * Resolved per request rather than captured once: a client is created per request on the server,
   * and a token that was baked in at module load would outlive the session it belongs to.
   * Returning `undefined` sends the request unauthenticated.
   */
  token?: () => string | undefined | Promise<string | undefined>;
  /** Extra headers on every request. Anything set here loses to `token`. */
  headers?: Record<string, string>;
  /** Injection point for tests, and for Next.js fetch options on the server. */
  fetch?: typeof globalThis.fetch;
}

/**
 * A factory, never a shared instance. On the server a module-level client would carry one request's
 * credentials into the next request — a cross-user token leak — so each request builds its own.
 */
export function createApiClient(config: ApiClientConfig): ApiClient {
  const client = createClient<paths>({
    baseUrl: config.baseUrl,
    fetch: config.fetch,
    headers: { Accept: "application/json", ...config.headers },
  });

  if (config.token) client.use(bearerAuth(config.token));

  return client;
}

function bearerAuth(token: NonNullable<ApiClientConfig["token"]>): Middleware {
  return {
    async onRequest({ request }) {
      const value = await token();
      if (value) request.headers.set("Authorization", `Bearer ${value}`);

      return request;
    },
  };
}

/**
 * openapi-fetch returns `{data, error}` and throws only on transport failure, which is right for
 * call sites that branch on the error and wrong for the ones that just want the payload. Pass a
 * result through here to get the payload or an {@link ApiError}.
 */
export function unwrap<T>(result: {
  data?: T;
  error?: unknown;
  response: Response;
}): T {
  if (result.error !== undefined) throw ApiError.from(result.response, result.error);

  if (result.data === undefined) {
    throw new ApiError(`HTTP ${result.response.status} carried no body.`, {
      status: result.response.status,
    });
  }

  return result.data;
}
