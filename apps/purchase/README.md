# purchase

The customer-facing purchase flows: new order, renewal, gift, coupon redemption, the homeschool
discount and the AWE variant, plus the shared checkout and success pages. Taking over from
membership's Laravel `orders/` pages.

Next.js 16 (App Router), Tailwind CSS v4, shadcn/ui on [`@base-ui/react`](https://base-ui.com).

## Running it

From the repository root, because the task graph builds this app's dependencies first:

```bash
pnpm install
pnpm turbo run dev --filter=purchase     # http://localhost:3000
```

Copy `.env.example` to `.env.local` and point `API_URL` at a `/api/v2` root. Every brand read is
server-side, so the browser never sees that host.

| Command            | What it does                       |
| ------------------ | ---------------------------------- |
| `pnpm dev`         | Development server on port 3000    |
| `pnpm build`       | Production build                   |
| `pnpm start`       | Serve the production build         |
| `pnpm check-types` | `next typegen` then `tsc --noEmit` |
| `pnpm lint`        | ESLint                             |

## URLs

The market slug is the first path segment, and it is the only way a brand is chosen — no GeoIP, no
redirect from a guess, no cookie. An unknown slug is a hard 404.

The slug is not the brand's identity: `au`, `uk` and `us` map onto membership's brand codes
`MOL_AU`, `MOL_UK` and `MOL_US` through a static table in `lib/brands/brand-code.ts`. The mapping is
a decision rather than a derivation — more than one brand can serve one country.

```
/{market}                          new order
/{market}/renew                    renewal
/{market}/gift                     gift
/{market}/gift/homeschool          gift, homeschool
/{market}/homeschool-discount      new order, homeschool
/{market}/homeschool-discount/renew
/{market}/awe                      AWE
/{market}/subscribe                coupon redemption
/{market}/checkout/{uuid}          Stripe Embedded Checkout
/{market}/success/{uuid}           order success
```

`/` has no page: there is nothing to serve without a brand, so it 404s.

Which brands exist is decided by `BrandCode` in
[`@workspace/openapi-v2`](../../packages/openapi-v2/README.md), not by this app.

## Layout

```text
src/
  app/            App Router pages, the root layout, globals.css
    [market]/     every flow, under the market-slug segment
  components/
    ui/           shadcn/ui
  lib/
    api/          the typed v2 client, configured for this app
    brands/       slug-to-code mapping, brand resolution and the brand read
    utils/
public/           images ported from membership
docs/research/    background investigation, not specification
```

`@/*` maps to `./src/*`.

## Before changing anything

`CLAUDE.md` holds this app's conventions — component placement, the `*Form` contract, the Base UI
gotchas, what the pages deliberately do not do yet. `../../CONTEXT.md` holds the brand vocabulary.
Both are short and both will save you a wrong guess.
