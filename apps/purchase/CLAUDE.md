@AGENTS.md

# apps/purchase

The customer-facing purchase flows, taking over from membership's Laravel `orders/` pages. The
repository-wide rules are in `../../.claude/CLAUDE.md`; the market vocabulary is in
`../../CONTEXT.md`. What follows holds for this app only.

## State

Every flow page renders its real UI, ported from membership's blades, and **no flow has any logic**.
No form has an `onSubmit`, nothing validates, nothing calls the API. Where a page needs data there is
no read for — plan prices, the country list — a clearly-named `PLACEHOLDER_*` constant stands in,
marked at its definition. Where membership branches on the order (the checkout's paid / cancelled /
not-found panels, the success page's per-type wording), only the main case is rendered.

No form library is installed. Picking one is a decision to make when the first form gets an
`onSubmit`, not a thing to assume from the comments.

## Structure

- **App Router** under `src/app/`, path alias `@/*` → `./src/*`. Route params are typed with Next's
  generated `PageProps<'/route'>` / `LayoutProps<'/route'>` globals — which is why `check-types` runs
  `next typegen` first. `params` is a Promise; `await` it.
- **Every `[market]` page resolves through `requireMarket(segment)`** — membership check, then the
  market read, then `notFound()`. Matching is exact and lower-case: `/AU` is a 404, not a redirect,
  and there is no GeoIP routing.
- **Module file naming** is kebab-case, one exported concept per file (`require-market.ts`,
  `market-code.ts`, `utils/cn.ts`). A dot suffix marks a _kind_ of file: `*.api.ts` is an API
  boundary and the seam where a real `fetch` lands. There is no `services/` layer — modules group by
  domain (`lib/markets/`), not by technical role. `lib/utils/` is the exception that proves it:
  standalone helpers belonging to no domain, still one per file.
- **Component placement follows use, not category.** Used by more than one route → `src/components/`.
  Used by exactly one route → a `_components/` folder beside that route. Used by exactly one caller
  and making no decision of its own → no component at all; write the markup inline where it renders.
  `src/components/ui/` is for shadcn/ui only. Import everything through `@/components/…`, including
  siblings — no `../../` climbs.
- **A `*Form` component returns a `<form>`, nothing more.** The card around it is the page's:
  `SecureCheckoutCard` for the paid flows (it carries the "Secure Checkout" lock, which doubles as
  their `<h1>`), a plain `Card` for the coupon redemption. A form is then droppable into a different
  frame without unwrapping it first.

## UI

- **shadcn/ui in the `base-vega` style** (see `components.json`), built on **`@base-ui/react`
  primitives — not Radix**. They live in this app, not a shared package: this is the only consumer,
  and a second front end gets its own set rather than inheriting a shape designed for one call site.
  Add components with the `shadcn` CLI rather than hand-writing them.
- Variants use `cva`; merge classes with `cn()` from `@/lib/utils/cn`, which `components.json` points
  its `utils` alias at, so CLI-added components land on the same path. Icons come from
  `lucide-react`.
- **Base UI selects need `items`.** A `Select` resolves its trigger label from the `items` prop, so
  options must be data (`[{ value, label }]`) mapped into `SelectItem`, not inline children —
  without it the trigger shows the raw submitted value.
- **Tailwind CSS v4** via `@tailwindcss/postcss` — config-less and CSS-first. Everything lives in
  `src/app/globals.css`: the imports, the `@theme inline` token map and the `oklch` tokens on
  `:root` / `.dark`. Style through the semantic tokens (`bg-background`, `text-muted-foreground`,
  `border-border`), not raw palette colours.
- **The brand-blue canvas is a literal, not a token.** `bg-[#0576c6]` on the root `<body>` is this
  application's chrome, ported from membership's `layouts/app.blade.php`. It is not a colour the
  token set offers, and nothing else should reach for it.
- **Dark mode is class-based** (`@custom-variant dark (&:is(.dark *))`) and **inert**: nothing adds
  the class, there is no theme provider, and the hard-coded canvas would not survive one. `dark:`
  variants are dead code until that changes.
- **Fonts**: `layout.tsx` loads Instrument Sans as `--font-sans` and Geist Mono as `--font-mono`.
