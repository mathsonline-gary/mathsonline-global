# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A Turborepo monorepo for **v2 only**. It holds the OpenAPI description of `/api/v2`, the applications that implement and consume it, and one task graph covering all of them.

`mathsonline/membership` is a separate repository that keeps serving `/api/v1`. The two versions share nothing: `membership` owns `api/v1/`, this repository owns the v2 description. Do not look for v2 files there, and do not add v1 files here.

## Layout

```
api/            # the OpenAPI 3.1 description of /api/v2 — @workspace/api-contracts
apps/www/       # Next.js front end
packages/ui/    # shared components — @workspace/ui
packages/typescript-config/, packages/eslint-config/
```

The description sits at the repository root rather than under `packages/` because it is a language-neutral artifact: the front ends consume generated TypeScript, and the back end will read the bundled YAML off disk. It carries no version segment — this repository serves exactly one API version.

## The description leads the implementation

**The description is written first, and the implementation follows it.** A route, controller or FormRequest is only complete once it matches `api/`. The description is hand-maintained, never generated from code.

- `api/dist/` (bundle, generated types, HTML docs) is generated and gitignored. Never edit or commit it.
- Only the shared API client should import the generated types directly, so a breaking description change produces one compile error rather than one per call site.
- `api/README.md` is the authority on the description's layout and conventions. Read it before adding a path or component.

## Commands

Run everything through the task graph from the repository root; that is also what CI runs.

```bash
pnpm install                    # bootstrap; pnpm workspaces, Node >= 22
pnpm turbo run lint check-types build
pnpm dev                        # every app's dev server
pnpm format                     # prettier
```

Scope to one package with `--filter`, e.g. `pnpm turbo run build --filter=@workspace/api-contracts`.

A Laravel back end and the `student`, `teacher` and `parent` front ends are planned but not present yet. There is no test suite and no CI workflow in the repository — add the commands here when they land.

## Git conventions

- Default branch is `main`.
- The initial work happened on a branch named `init`.
- The v2 description was migrated from `membership` with `git subtree`, so its history predates this repository. Use `git log --follow` on files under `api/`.
