# api-v2

The Laravel back end implementing `/api/v2` — the one server behind `purchase` and the signed-in
front ends. `mathsonline/membership` keeps serving `/api/v1`; the two share nothing.

A pure API: no Blade views, no Vite, no asset pipeline. JSON in, JSON out.

## The description leads

Every path is written in [`@workspace/openapi-v2`](../../packages/openapi-v2/README.md) first. A
route, controller or FormRequest is finished when it matches the description — never the other way
round, and the description is never generated from this code.

## Running it

```bash
composer run setup       # install, .env, key, migrate
composer run dev         # php artisan serve
```

From the repository root, `pnpm turbo run dev --filter=api-v2` does the same through the task graph.

| Command     | What it does             |
| ----------- | ------------------------ |
| `pnpm dev`  | `php artisan serve`      |
| `pnpm lint` | Pint, check-only         |
| `pnpm test` | Pest, via `artisan test` |

The `package.json` carries no dependencies. It exists so Turborepo can reach the PHP tooling from
the root task graph; Composer owns everything this app actually installs.

## Layout

```text
app/            Http/, Models/, Providers/
routes/api.php  every v2 path, mounted at /api
config/
database/       migrations, factories, seeders
tests/          Pest — Feature/ and Unit/
```

## Not decided yet

Authentication. The description declares no `securitySchemes`, so no auth package is installed.
When the scheme is described, `php artisan install:api --force` adds Sanctum (or `--passport`)
and rewrites `routes/api.php`.
