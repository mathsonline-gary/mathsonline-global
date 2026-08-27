# mathsonline-global

A Turborepo monorepo for **v2**: the OpenAPI description of `/api/v2`, the Laravel back end that
implements it, the front ends that consume it, and one task graph over all of them.

`mathsonline/membership` is a separate repository and keeps serving `/api/v1`. The two versions share nothing — `membership` owns `api/v1/`, this
repository owns v2. There are no v2 files there and no v1 files here.

## The description leads the implementation

`packages/openapi-v2` is written first and by hand; it is never generated from code. A route,
controller or FormRequest is finished when it matches the description, and a path that is not
described is not built.

`@workspace/api-client` is the only package that imports the generated types, so a breaking
description change surfaces as one compile error instead of one per call site. Applications import
the client.

## Layout

```text
packages/
  openapi-v2/          the OpenAPI 3.1 description of /api/v2  (@workspace/openapi-v2)
  api-client/          typed HTTP client over it               (@workspace/api-client)
  typescript-config/   shared tsconfig bases
  eslint-config/       shared ESLint config
apps/
  api-v2/              Laravel back end serving /api/v2
  purchase/            Next.js — the customer purchase flows
  www/                 marketing site        — placeholder
  cms/                 content management    — placeholder
  admin/               internal staff tools  — placeholder
  student/             signed-in student     — placeholder
  teacher/             signed-in teacher     — placeholder
  parent/              signed-in parent      — placeholder
```

The description sits under `packages/` because it is a workspace package like any other. Its name
carries the version so that the back-end directory does not have to: one thing called `api` per
repository.

A placeholder is a directory with a README and no `package.json`, so pnpm and Turborepo skip it
until there is something to build.

**Each application carries its own UI components.** There is no shared component package — one
consumer's shape is not a design system. Whatever genuinely converges gets extracted once there
are two real call sites to design against.

## Getting started

Requires Node >= 22 and pnpm 11. The back end additionally needs PHP 8.5 and Composer.

```bash
pnpm install
pnpm turbo run lint check-types test build
```

| Command            | What it does                                         |
| ------------------ | ---------------------------------------------------- |
| `pnpm dev`         | every application's dev server                       |
| `pnpm mock`        | Prism mock server over the description, on port 4010 |
| `pnpm lint`        | ESLint, Redocly and Pint                             |
| `pnpm check-types` | `tsc --noEmit`                                       |
| `pnpm test`        | Vitest and Pest, once                                |
| `pnpm build`       | bundle the description, build the applications       |
| `pnpm format`      | Prettier                                             |

Run tasks from the repository root so the graph builds each package's dependencies first. Scope to
one package with `--filter`:

```bash
pnpm turbo run build --filter=@workspace/openapi-v2
```

`mock` is deliberately not part of `dev`: an application pointed at a real back end should not also
spin up a mock. Point one at it with `API_URL=http://127.0.0.1:4010` — no `/api/v2` prefix, because
Prism mounts paths at the root.

## Commits are gated

Husky runs `lint-staged`, which formats the staged files with Prettier and then runs
`pnpm turbo run lint check-types test` over the whole graph. The gate is whole-graph rather than
scoped to staged paths because Redocly lints from the description's entrypoint, and type errors and
test breakage are cross-file. Turborepo restores unaffected packages from cache, so it stays cheap.

A commit whose lint, type-check or tests fail is refused. `git commit --no-verify` skips the hook —
for work-in-progress branch commits, not for a red `main`.

There is no CI workflow yet ([#1](https://github.com/mathsonline-gary/mathsonline-global/issues/1)).

## Where to look next

| File                            | What it settles                                   |
| ------------------------------- | ------------------------------------------------- |
| `CONTEXT.md`                    | the project's vocabulary — read before naming     |
| `packages/openapi-v2/README.md` | the description's layout and conventions          |
| `packages/api-client/README.md` | the client                                        |
| `apps/*/README.md`              | each application                                  |
| `.claude/CLAUDE.md`             | conventions for agents working in this repository |
