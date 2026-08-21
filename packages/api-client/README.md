# API client

The typed HTTP client for `/api/v2`. `openapi-fetch` with the types generated from
[`@workspace/openapi-v2`](../openapi-v2/README.md) as its type parameter, so a request path,
parameter or response field that does not exist fails to compile.

```ts
import { createApiClient, unwrap, ApiError } from "@workspace/api-client";

const api = createApiClient({
  baseUrl: process.env.API_URL!,
  token: () => session?.accessToken,
});
const { data } = await api.GET("/markets/{marketCode}", {
  params: { path: { marketCode: "au" } },
});
```

## Rules

- **`createApiClient` is a factory, and call sites must keep it one.** A module-level client on the
  server carries one request's credentials into the next request — a cross-user token leak. Build a
  client per request; `token` is a callback for the same reason.
- **This package is the only importer of `@workspace/openapi-v2/types`.** Apps import from here. A
  breaking description change then surfaces as one compile error rather than one per call site.
- **`unwrap` turns `{data, error}` into the payload or an `ApiError`.** `openapi-fetch` throws only
  on transport failure, which suits call sites that branch on the error and not the ones that just
  want the payload.
- **The `{data: …}` envelope is not unwrapped here.** It is part of the response schema, so it stays
  visible in the types; each app's data layer strips it.
- **No app-specific policy.** Base URL and credentials are arguments, not defaults. A back office
  authenticating with a session and a public storefront sending nothing both use this package
  unchanged, and neither one's needs are baked in as a default for the other to override.

## `ApiError`

Normalises what the description guarantees about failures: Laravel's `{message}` envelope on any
JSON request, 422's per-field messages, 429's `Retry-After`.

| Member              | Source                                                                 |
| ------------------- | ---------------------------------------------------------------------- |
| `status`            | the response status                                                    |
| `message`           | the body's `message`, falling back to the status text                  |
| `validationErrors`  | 422 — field (dot notation for nested and array fields) to its messages |
| `retryAfterSeconds` | 429 — the `Retry-After` header, when set                               |
| `body`              | the parsed error body, untouched                                       |

Plus `isValidationError`, `isUnauthenticated`, `isNotFound`, `isRateLimited` and `isServerError` for
the branches worth naming. 401 means no or expired credentials; 403 means authenticated and refused.

## Why a separate package

The client is not generated — it is `openapi-fetch` plus configuration — so it could have lived
inside the description package. Splitting it keeps that package free of a TypeScript toolchain it
otherwise has no use for (`tsconfig.json`, eslint, `typescript`, `openapi-fetch`). Nothing here
resolves through a `tsconfig` `paths` patch: `moduleResolution: bundler` follows the dependency's
`exports` map to the generated `.ts` directly.

## Commands

```bash
pnpm lint          # eslint
pnpm check-types   # tsc — waits for the description's build via the task graph
```
