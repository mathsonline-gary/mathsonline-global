# Context

The project's vocabulary. A glossary and nothing else — no implementation detail, no decisions, no
spec. When a term here conflicts with how a name is used in code or in a conversation, this file is
what the conversation has to resolve against.

Today it covers one family of terms: the brand. It grows a section when a second family needs one.

## Brand

A **brand** is one country-specific storefront. Everything a customer can buy is bought from exactly
one brand, and the brand decides the currency, the contact points and which flows are available. One
brand is one row in membership's `brands` table, and this is membership's own word for it — the two
systems name the thing the same way on purpose.

A brand's **code** is its identity: `MOL_AU`, `MOL_UK`, `MOL_US`. Upper-case, stable, and
membership's, taken verbatim from `brands.code`. `BrandCode` in the `/api/v2` description enumerates
every code that exists; a brand that is not in that enum is not a brand.

> The words **market code** and **slug** meant this in earlier work and are retired. One name for
> one concept.

A brand's **market** is the country it sells into, as display text: "Australia", "United Kingdom".
It is a label, never a routing key, and it is not derivable from the code. It arrives on the wire as
`Brand.market`, which is what membership calls the column too.

A brand's **name** is the product name the customer sees it under: "MathsOnline". It varies from
brand to brand — membership also serves "MathsBuddy", "MathOnline" and "CTCMath" — so it is
configuration, it crosses the wire as `Brand.name`, and copy interpolates it rather than spelling it
out.

A **market slug** is the first path segment of a purchase URL: `au`, `uk`, `us`. Lower-case, and the
only market vocabulary that survives — it is what a customer reads in the address bar, not an
identity. Each slug maps to exactly one brand code through a static table in `apps/purchase`. The
mapping is a decision rather than a derivation, because more than one brand can serve one country:
membership's `MOL_US` and `CTC_US` both sell into the United States.

> ⚠️ A slug is not a code. `/au` is a URL, `MOL_AU` is the brand. Nothing outside that one mapping
> table takes a slug as a brand's identity, and `/api/v2` never sees one.

## Flow

A **flow** is one purchase journey a customer can complete, from its first form to the success page:
new order, renewal, gift, coupon redemption, homeschool discount, and the AWE variant. Checkout and
success are shared endings, not flows of their own.

Every flow belongs to a brand — there is no brand-neutral flow, and no landing page above one.
A flow can be unavailable in a brand without the brand being unavailable.

The **trial** is a flow the customer starts without paying. It is named here because it is planned,
not present: which of its two forms a brand runs is not something `/api/v2` describes yet.
