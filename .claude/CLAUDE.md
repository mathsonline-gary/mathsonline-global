# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A Turborepo monorepo for **v2 only**. It holds the OpenAPI description of `/api/v2`, the applications that implement and consume it, and one task graph covering all of them.

`mathsonline/membership` is a separate repository that keeps serving `/api/v1`. The two versions share nothing: `membership` owns `api/v1/`, this repository owns the v2 description. Do not look for v2 files there, and do not add v1 files here.

## Layout

```
packages/openapi-v2/   # the OpenAPI 3.1 description of /api/v2 — @workspace/openapi-v2
packages/api-client/   # typed HTTP client over it — @workspace/api-client
packages/ui/           # shared components — @workspace/ui
packages/typescript-config/, packages/eslint-config/
apps/www/              # Next.js front end
```

The description is a workspace package like any other, so it lives under `packages/` and Turborepo picks it up from the `packages/*` glob. The name carries the version because the directory that will hold the Laravel back end is `apps/api` — one thing called `api` per repository.

## The description leads the implementation

**The description is written first, and the implementation follows it.** A route, controller or FormRequest is only complete once it matches the description. The description is hand-maintained, never generated from code.

- `packages/openapi-v2/dist/` (bundle, generated types, HTML docs) is generated and gitignored. Never edit or commit it.
- `@workspace/api-client` is the only place that imports the generated types, so a breaking description change produces one compile error rather than one per call site. Apps import the client, never `@workspace/openapi-v2/types` directly.
- The description package holds the description and its generated output only. Type generation belongs there because a package builds from its dependency's output, not its source.
- `createApiClient` is a factory and must stay one. A module-level client on the server carries one request's credentials into the next request; build a client per request.
- `packages/openapi-v2/README.md` is the authority on the description's layout and conventions. Read it before adding a path or component; `packages/api-client/README.md` covers the client.

## Commands

Run everything through the task graph from the repository root; that is also what CI runs.

```bash
pnpm install                    # bootstrap; pnpm workspaces, Node >= 22
pnpm turbo run lint check-types build
pnpm dev                        # every app's dev server
pnpm format                     # prettier
```

Scope to one package with `--filter`, e.g. `pnpm turbo run build --filter=@workspace/openapi-v2`.

A Laravel back end and the `student`, `teacher` and `parent` front ends are planned but not present yet. There is no test suite and no CI workflow in the repository — add the commands here when they land.

## Git conventions

- Default branch is `main`.
- The initial work happened on a branch named `init`.
- The v2 description was migrated from `membership` with `git subtree`, so its history predates this repository. Use `git log --follow` on files under `packages/openapi-v2/`.
