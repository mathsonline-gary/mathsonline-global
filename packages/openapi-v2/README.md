# API contracts

Everything describing `/api/v2`, the only API version this repository serves — which is why no path
here carries a version segment. It was migrated out of `mathsonline/membership` (`api/v2/`) with its
history intact; that repository keeps `api/v1/` and shares nothing with this description.

Nothing here is implemented yet — the back end has no `/api/v2` group. **The description is written
first, and the implementation follows it.**

This directory is a workspace package, `@workspace/openapi-v2`, and it holds the description and
nothing else: no runtime dependency, no application code. Consumers depend on it rather than
reaching for these files by path — the back end reads `dist/openapi.yaml` off disk, and
[`@workspace/api-client`](../api-client/README.md) imports `@workspace/openapi-v2/types`. The name
carries the version so that it never competes with `apps/api`, the Laravel application that will
serve this description.

## Generated output

`build` produces three things in `dist/`, all from the same source and none of them committed:

| File           | Consumer                                      |
| -------------- | --------------------------------------------- |
| `openapi.yaml` | the bundle — what the back end reads off disk |
| `types.ts`     | `@workspace/api-client`, the only importer    |
| `index.html`   | human-readable docs                           |

The bundle and the types are two projections of one description: same input, no options to choose,
no policy. That is why generation lives here rather than in the client — a package should build from
its dependency's _output_, and generating the types in the client would mean reaching back into this
package's source YAML instead. It also keeps the types available to consumers that want no HTTP
runtime at all: mock handlers, contract-test scripts, fixture generators.

## Layout

```
packages/openapi-v2/
├── README.md
├── package.json           # @workspace/openapi-v2
├── redocly.yaml           # single `apis` entry: main
├── turbo.json             # carries "build before dependents type-check" to the graph
├── openapi.yaml           # root description — index of every path and component
├── paths/
│   └── markets.yaml       # every path item under /markets
├── schemas/
│   ├── market.yaml        # Market, MarketCode
│   ├── error.yaml         # Error, ValidationError
│   └── pagination.yaml    # PaginationLinks, PaginationMeta, PaginationLink
├── responses/
│   └── errors.yaml        # 401, 403, 404, 405, 422, 429, 500
└── dist/                  # generated, gitignored, never edited
    ├── openapi.yaml       # bundle — what the back end reads
    ├── types.ts           # TypeScript types — what the API client imports
    └── index.html         # HTML docs
```

## Commands

From the repository root, through the task graph:

```bash
pnpm turbo run lint --filter=@workspace/openapi-v2   # validate the description
pnpm turbo run build --filter=@workspace/openapi-v2  # bundle + types + docs → dist/
```

Or directly, from this directory:

```bash
pnpm lint    # redocly lint
pnpm build   # bundle, HTML docs, then generate dist/types.ts
```

Both must pass before a description change is done. `lint` catches invalid schemas; the bundle step
in `build` is what catches a `$ref` that escapes its file or points at a pointer that does not
exist, which linting does not always report.

## Mock server

The back end does not exist yet, so the description serves as one. `pnpm mock` from the repository
root runs [Prism](https://github.com/stoplightio/prism) over `dist/openapi.yaml`, rebuilding the
bundle first:

```bash
pnpm mock                                  # → http://127.0.0.1:4010
curl http://127.0.0.1:4010/markets/au
```

Point an app at it with `API_URL=http://127.0.0.1:4010` — **without** the `/api/v2` prefix. Prism
mounts the paths at the root and ignores the `servers` base path, so the prefixed URL would 404.

Three things follow from mocking the description rather than hand-writing fixtures:

- **The `examples` are the mock data.** Prism reads the OpenAPI 3.1 `examples` arrays on each
  schema, so `GET /markets/au` returns `"Australia"`/`"AUD"` and not `"string"`. An example added
  for the docs improves the mock at the same time, which is the reason to keep writing them.
- **Requests are validated against the description.** `GET /markets/zz` gets a 422 naming the
  `enum` violation, so a front end that builds a URL the API would reject finds out immediately.
- **Error branches are reachable** with the `Prefer` header, without editing anything:
  `curl -H 'Prefer: code=404' http://127.0.0.1:4010/markets/au`.

Three caveats:

- **The response does not depend on the request.** `GET /markets/us` returns the `au` example,
  because an example is a constant and Prism has nothing to vary it with. A country picker will
  appear to ignore the country. This is the mock's sharpest edge — treat a response as proof the
  shape is right, never that the routing is.

  Named media-type `examples` plus `Prefer: example=uk` _can_ select between markets, but selection
  comes from the header, not `marketCode`, and each market's payload becomes hand-maintained.
  Rejected for now; revisit if a flow needs two markets side by side.

- **Undeclared status codes get a Prism-shaped body.** For a code the path item does not list,
  Prism synthesises RFC 7807 (`{type, title, status, detail}`), which is _not_ the Laravel
  `{message, errors}` shape. Do not build UI against it; declare the response instead.
- **`--dynamic` is off.** It generates random data from the schemas rather than replaying the
  examples, which loses the realistic values above and makes responses differ between runs.

Nothing here is a substitute for running against the real Laravel once it exists.

## Conventions

| Item                            | Rule                                                                                             | Example                                            |
| ------------------------------- | ------------------------------------------------------------------------------------------------ | -------------------------------------------------- |
| Path file                       | one per resource — the first path segment                                                        | `/markets/*` → `paths/markets.yaml`                |
| Path file keys                  | the full path strings, exactly as `paths` spells them                                            | `/markets/{marketCode}:`                           |
| Root `$ref` to a path item      | JSON Pointer into that file, `/` escaped as `~1`, directly under the same URL written as the key | `'./paths/markets.yaml#/~1markets~1{marketCode}'`  |
| Component file, many components | lower-case resource or group name, components as top-level keys                                  | `schemas/market.yaml` → `#/Market`, `#/MarketCode` |
| Component file, one component   | PascalCase, filename = component name                                                            | (none yet)                                         |
| Component directory             | mirrors the OpenAPI key exactly                                                                  | `responses/`, not `error-responses/`               |

- **`openapi.yaml` registers every component**, not just the widely-used ones: one file that lists
  what exists and where it lives.
- **A path item is keyed by URL, not by verb.** All methods for one URL share one keyed block and
  its `parameters`, which is why the keys are path strings and not Laravel-ish labels (`show`,
  `index`) — those name operations, and would be a lie the moment a second method appears on the
  same URL. The Laravel verb lives in `operationId` (`showMarket`), where it cannot drift.
- **The description is hand-maintained**, not generated from the code. Because it leads the
  implementation, a route or FormRequest is only complete once it matches the description — and
  `dist/` is generated, so it is never edited or committed.

## Framework responses and schemas

Transcribed from a running Laravel, not from memory — `meta.links[]` carries a `page` key in
Laravel 11+, for instance, which older references omit. Re-check on a major upgrade.

| Component                                       | Where it comes from                                       |
| ----------------------------------------------- | --------------------------------------------------------- |
| `responses/errors.yaml#/Unauthenticated` (401)  | `AuthenticationException` → `Unauthenticated.`            |
| `responses/errors.yaml#/Forbidden` (403)        | `AuthorizationException` → `This action is unauthorized.` |
| `responses/errors.yaml#/NotFound` (404)         | unmatched route, or route-model binding miss              |
| `responses/errors.yaml#/MethodNotAllowed` (405) | route matched, method didn't                              |
| `responses/errors.yaml#/ValidationError` (422)  | `ValidationException` → `{message, errors}`               |
| `responses/errors.yaml#/TooManyRequests` (429)  | `ThrottleRequests` → `Too Many Attempts.`, `Retry-After`  |
| `responses/errors.yaml#/ServerError` (500)      | any other exception → `Server Error`                      |
| `schemas/error.yaml`                            | `Handler::convertExceptionToArray`                        |
| `schemas/pagination.yaml`                       | `PaginatedResourceResponse` + `LengthAwarePaginator`      |

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
