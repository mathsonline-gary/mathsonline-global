# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A Turborepo monorepo for **v2 only**. It holds the OpenAPI description of `/api/v2`, the applications that implement and consume it, and one task graph covering all of them.

`mathsonline/membership` is a separate repository that keeps serving `/api/v1`. The two versions share nothing: `membership` owns `api/v1/`, this repository owns the v2 description. Do not look for v2 files there, and do not add v1 files here.

`CONTEXT.md` at the root is the project's glossary. Read it before naming anything in the market domain — it records, among other things, that the wire's `Market.name` is the country and not the brand.

## Layout

```
packages/openapi-v2/   # the OpenAPI 3.1 description of /api/v2 — @workspace/openapi-v2
packages/api-client/   # typed HTTP client over it — @workspace/api-client
packages/typescript-config/, packages/eslint-config/
apps/api-v2/           # Laravel back end serving /api/v2
apps/purchase/         # Next.js front end for the customer purchase flows
apps/www/, apps/cms/, apps/admin/, apps/student/, apps/teacher/, apps/parent/   # placeholders
```

A placeholder is a directory holding a README and no `package.json`, so pnpm and Turborepo skip it
until there is an application to build. Do not scaffold one speculatively: `www` was a
`create-next-app` scaffold once and was removed because it served nothing.

The description is a workspace package like any other, so it lives under `packages/` and Turborepo picks it up from the `packages/*` glob. Both it and the back end carry the version in their name — `@workspace/openapi-v2` and `apps/api-v2` — because this repository is v2 only and an unversioned `api` would invite the question of which version it serves.

**Each application carries its own UI components.** There is no shared component package: `packages/ui` existed and was removed, because one consumer's shape is not a design system and the indirection cost more than it saved. A second front end gets its own `components/ui/`; whatever genuinely converges can be extracted then, with two real call sites to design against. Do not reintroduce a shared package to hold a component that has one caller.

## The description leads the implementation

**The description is written first, and the implementation follows it.** A route, controller or FormRequest is only complete once it matches the description. The description is hand-maintained, never generated from code.

- `packages/openapi-v2/dist/` (bundle, generated types, HTML docs) is generated and gitignored. Never edit or commit it.
- `@workspace/api-client` is the only place that imports the generated types, so a breaking description change produces one compile error rather than one per call site. Apps import the client, never `@workspace/openapi-v2/types` directly.
- The description package holds the description and its generated output only. Type generation belongs there because a package builds from its dependency's output, not its source.
- `createApiClient` is a factory and must stay one. A module-level client on the server carries one request's credentials into the next request; build a client per request.
- `packages/openapi-v2/README.md` is the authority on the description's layout and conventions. Read it before adding a path or component; `packages/api-client/README.md` covers the client.

## Commands

Run everything through the task graph from the repository root. There is no CI yet, so this is the only thing that runs the gate besides the pre-commit hook.

```bash
pnpm install                    # bootstrap; pnpm workspaces, Node >= 22
pnpm turbo run lint check-types test build
pnpm dev                        # every app's dev server
pnpm mock                       # Prism mock server over the description, on :4010
pnpm test                       # vitest and pest, once, everywhere configured
pnpm format                     # prettier
```

`mock` is deliberately not part of `dev`: an app pointed at a real back end should not also spin up
a mock. Point an app at it with `API_URL=http://127.0.0.1:4010` — no `/api/v2` prefix, because
Prism mounts paths at the root. See `packages/openapi-v2/README.md`.

Scope to one package with `--filter`, e.g. `pnpm turbo run build --filter=@workspace/openapi-v2`.

There is no CI workflow in the repository (issue #1) — add the commands here when it lands.

### `apps/api-v2` in the task graph

It is a Laravel application, and it participates like any other package: its `package.json` carries
no dependencies and exists only so Turborepo can reach Pint and Pest from the root graph. Composer
owns what the app installs. `pnpm lint` there is `vendor/bin/pint --test`, `pnpm test` is
`php artisan test`; there is no `check-types` task because no static analyser is installed yet.

It serves JSON only — no Blade views, no Vite, no asset pipeline, and no `routes/web.php`. Do not
reintroduce the `laravel new` front-end scaffold. Authentication is deliberately not installed: the
description declares no `securitySchemes`, and `php artisan install:api` would pick Sanctum ahead of
that decision.

## Tests

Vitest in `@workspace/api-client` and `apps/purchase`; Pest in `apps/api-v2`.
`@workspace/openapi-v2` has none: it is YAML, and `redocly lint` is what checks it.

The two ecosystems place tests differently, and each follows its own convention rather than a
compromise between them.

**TypeScript packages.**

- A test sits beside the module it covers, as `<module>.test.ts`. There is no `__tests__/` or
  `tests/` directory; a test that has to be found by path rather than by proximity is a test
  nobody reads next to the code it describes.
- `packages/api-client` needs no vitest config — its tests are plain Node. `apps/purchase` has
  `vitest.config.mts` (jsdom, `@vitejs/plugin-react`, `resolve.tsconfigPaths` for the `@/*` alias)
  and `vitest.setup.ts` (jest-dom matchers, and Testing Library's `cleanup` after each test).
- Vitest does not type-check. `check-types` covers the test files too, because they are inside each
  package's `tsconfig.json` `include`, so the two tasks are complementary rather than redundant.
- Components are tested through the accessibility tree — `getByRole` over `getByTestId`. What a
  screen reader cannot find, a test should not be able to find either.
- `describe`/`it`/`expect` are imported from `vitest` rather than made global, so a test file
  declares its own dependencies like any other module.

**`apps/api-v2`.** Pest, under `tests/Feature/` and `tests/Unit/` — Laravel's own layout, which
`phpunit.xml` and Pest's discovery both assume. The beside-the-module rule above does not reach
here. `phpunit.xml` points the suite at in-memory SQLite, so the tests need no database set up.

## Commits are gated by a pre-commit hook

`husky` runs `lint-staged` on every commit, configured in `.lintstagedrc.mjs`. It does two things,
in order: `prettier --write` over the staged files, then `pnpm turbo run lint check-types test`
over the whole graph.

The second half runs whole-graph, not scoped to staged paths: `redocly lint` takes the
description's entrypoint, and type errors and test breakage are cross-file, so scoping to staged
files would miss failures caused elsewhere. Affordable because turbo restores unaffected packages
from cache.

A commit whose lint, type-check or tests fail is refused; prettier's fixes are re-staged. `git commit --no-verify` skips the hook — for work-in-progress branch commits, not a red `main`. No CI yet (issue #1).

## Git conventions

- Default branch is `main`.
- The initial work happened on a branch named `init`.
- The v2 description was migrated from `membership` with `git subtree`, so its history predates this repository. Use `git log --follow` on files under `packages/openapi-v2/`.
