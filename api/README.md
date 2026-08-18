# API contracts — v2

Everything describing `/api/v2`. Self-contained: v2 shares nothing with v1 except
[`redocly.yaml`](../redocly.yaml), including its layout, which deliberately differs from v1's.

Nothing here is implemented yet — `routes/api.php` has no `/api/v2` group. The description is
written first, and the Laravel side follows it.

## Layout

```
v2/
├── README.md
├── openapi.yaml           # root description — index of every path and component
├── paths/
│   └── markets.yaml       # every path item under /markets
├── schemas/
│   ├── market.yaml        # Market, MarketCode
│   ├── error.yaml         # Error, ValidationError
│   └── pagination.yaml    # PaginationLinks, PaginationMeta, PaginationLink
├── responses/
│   └── errors.yaml        # 401, 403, 404, 405, 422, 429, 500
└── dist/                  # bundle + docs (gitignored, never edited)
    ├── openapi.yaml
    └── index.html
```

## Commands

```bash
npm run api:lint      # validate every version
npm run api:build:v2  # bundle + docs → v2/dist/
```

## Conventions

| Item | Rule | Example |
| --- | --- | --- |
| Path file | one per resource — the first path segment | `/markets/*` → `paths/markets.yaml` |
| Path file keys | the full path strings, exactly as `paths` spells them | `/markets/{marketCode}:` |
| Root `$ref` to a path item | JSON Pointer into that file, `/` escaped as `~1`, directly under the same URL written as the key | `'./paths/markets.yaml#/~1markets~1{marketCode}'` |
| Component file, many components | lower-case resource or group name, components as top-level keys | `schemas/market.yaml` → `#/Market`, `#/MarketCode` |
| Component file, one component | PascalCase, filename = component name | (none yet) |
| Component directory | mirrors the OpenAPI key exactly | `responses/`, not `error-responses/` |

- **`openapi.yaml` registers every component**, not just the widely-used ones: one file that lists
  what exists and where it lives.
- **A path item is keyed by URL, not by verb.** All methods for one URL share one keyed block and
  its `parameters`, which is why the keys are path strings and not Laravel-ish labels (`show`,
  `index`) — those name operations, and would be a lie the moment a second method appears on the
  same URL. The Laravel verb lives in `operationId` (`showMarket`), where it cannot drift.
- **The description is hand-maintained.** A route or FormRequest change is only complete once the
  description matches it.

## Framework responses and schemas

Transcribed from the installed Laravel, not from memory — `meta.links[]` carries a `page` key in
Laravel 11+, for instance, which older references omit. Re-check on a major upgrade.

| Component | Where it comes from |
| --- | --- |
| `responses/errors.yaml#/Unauthenticated` (401) | `AuthenticationException` → `Unauthenticated.` |
| `responses/errors.yaml#/Forbidden` (403) | `AuthorizationException` → `This action is unauthorized.` |
| `responses/errors.yaml#/NotFound` (404) | unmatched route, or route-model binding miss |
| `responses/errors.yaml#/MethodNotAllowed` (405) | route matched, method didn't |
| `responses/errors.yaml#/ValidationError` (422) | `ValidationException` → `{message, errors}` |
| `responses/errors.yaml#/TooManyRequests` (429) | `ThrottleRequests` → `Too Many Attempts.`, `Retry-After` |
| `responses/errors.yaml#/ServerError` (500) | any other exception → `Server Error` |
| `schemas/error.yaml` | `Handler::convertExceptionToArray` |
| `schemas/pagination.yaml` | `PaginatedResourceResponse` + `LengthAwarePaginator` |

Laravel emits these whether or not the description opts in, so they are facts about the API rather
than design claims. A path item lists only the codes it actually distinguishes — `showMarket`
documents `200` and `404`, not the whole table.

## Response envelope

v2 wraps in `{data: …}`, the `JsonResource` default. v1 hand-builds `{error, message, data}`; the
two versions are independent and v2 does not follow it.

OpenAPI has no generics, so `{data: T}` cannot be declared once and reused — every operation
declares its own wrapper. Where the wrapper is five lines used by one operation, it is written
inline in the path item rather than given a `MarketResource` component of its own. Only the
non-generic leaves are components: `PaginationLinks`, `PaginationMeta`, `PaginationLink`.

Those pagination leaves are unused so far. When the first collection endpoint lands, decide whether
to keep `meta.links[]` at all — it exists to render Blade pagination controls and is mostly noise
for an API client. Dropping it via a custom `paginationInformation()` is easier before anything
consumes it.
