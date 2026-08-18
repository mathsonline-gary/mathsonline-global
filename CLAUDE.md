# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Status

This repository is a scaffold. As of the initial commit it contains only `README.md`, `.gitignore`, and this file — no application code, dependency manifests, or tests yet.

Until code lands, treat everything below as intent rather than fact, and verify against the working tree before relying on it. When the first application code is committed, replace the placeholder sections with the real commands and architecture.

## Intended stack

`.gitignore` was generated from the toptal template for `nextjs` and `laravel` (alongside OS and editor entries for macOS, Linux, Windows, JetBrains IDEs, VS Code, and Sublime Text). That implies the planned shape of the project:

- A Laravel (PHP) backend, likely providing an HTTP/JSON API.
- A Next.js (React/TypeScript) frontend.

The two may end up in one repository (monorepo, e.g. `api/` plus `web/`) or the template may simply be broad. Confirm the actual layout before assuming either.

## Commands

Not yet established — there is no `composer.json`, `package.json`, `Makefile`, or CI config in the repository.

When the stack is scaffolded, document here:

- Install and bootstrap (dependencies, `.env` setup, database migration/seed).
- Local development servers for backend and frontend.
- Build and production compile.
- Lint and format, including whether formatting is enforced in CI.
- Test suite, and specifically how to run a **single** test file or test case.

## Git conventions

- Default branch is `main`.
- The initial work happened on a branch named `init`.
