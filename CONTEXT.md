# Context

The project's vocabulary. A glossary and nothing else — no implementation detail, no decisions, no
spec. When a term here conflicts with how a name is used in code or in a conversation, this file is
what the conversation has to resolve against.

Today it covers one family of terms: the market. It grows a section when a second family needs one.

## Market

A **market** is one country-specific storefront. Everything a customer can buy is bought from
exactly one market, and the market decides the currency, the contact points and which flows are
available. One market corresponds to one slug-bearing row in membership's `brands` table.

A market's **code** is its identity: `au`, `us`, `uk`. Lower-case, stable, two letters today but
nothing depends on the length. It is not an ISO 3166-1 alpha-2 country code — `uk` is not ISO, `GB`
is — so it is never validated against a country list. `MarketCode` in the `/api/v2` description
enumerates every code that exists; a market that is not in that enum is not a market.

> The word **slug** meant this same thing in earlier work and is retired. One name for one concept.

A market's **country** is the country it sells into, as display text: "Australia", "United Kingdom".
It is a label, never a routing key, and it is not derivable from the code.

> ⚠️ On the wire the country arrives as `Market.name`. In membership's database, `brands.name` is the
> **brand** — a different thing entirely. The same field name means two things in the two systems, so
> nothing is ever called `name` on this side of the boundary: the country is `country`, and the brand
> does not travel at all.

The **brand** is the name the customer sees the product under: "MathsOnline". There is one, so it is
not configuration and it does not cross the wire. Copy that names the brand names it literally.

## Flow

A **flow** is one purchase journey a customer can complete, from its first form to the success page:
new order, renewal, gift, coupon redemption, homeschool discount, and the AWE variant. Checkout and
success are shared endings, not flows of their own.

Every flow belongs to a market — there is no market-neutral flow, and no landing page above one.
A flow can be unavailable in a market without the market being unavailable.

The **trial** is a flow the customer starts without paying. It is named here because it is planned,
not present: which of its two forms a market runs is not something `/api/v2` describes yet.
