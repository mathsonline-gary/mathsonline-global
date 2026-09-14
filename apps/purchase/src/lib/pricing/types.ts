import type { components } from "@workspace/api-client";

/**
 * One priced membership option, as the customer is offered it.
 *
 * Money is a decimal amount in this pricing's own `currency` — never minor units, never
 * redenominated, and never the brand's currency, which is only the brand's default.
 */
export type Pricing = {
  /**
   * What a checkout sends back to say which option the customer chose. Opaque: never parse it.
   * `M3` is a family pricing for five students, so neither the letter nor the digit is a fact.
   */
  code: string;
  /** What the customer is charged, per billing period when `recurring`. */
  price: number;
  /** The undiscounted price. Shown struck through only when it is greater than `price`. */
  priceOriginal: number;
  /**
   * What the customer saves, `0` when nothing is. The API stores this rather than deriving it, so
   * it is not always `priceOriginal - price` and must not be recomputed from them.
   */
  priceSaved: number;
  /** ISO 4217 alpha-3, upper-case. Picks the symbol, nothing more. */
  currency: string;
  /** Whether `price` is charged every billing period until cancelled. */
  recurring: boolean;
  /** `1` is a single membership, anything greater a family one. */
  studentLimit: number;
  /** How long one charge covers, plus any bonus intervals granted free on top. */
  billingPeriod: {
    interval: components["schemas"]["Pricing"]["billing_period"]["interval"];
    count: number;
    extraCount: number;
  };
  /** How many payments the price is split into, `0` when it is paid in one. */
  installmentCount: number;
};

/**
 * Every pricing a brand offers, grouped as the customer sees it.
 *
 * Both groups are always present and never empty, whatever the query — so a caller renders the
 * same grid every time and never branches on what it asked for.
 *
 * **Array order is render order**, decided by the server: the ordering that matters is not
 * derivable from any one field, and two pricings can differ only by `installmentCount`.
 *
 * The two codes echo what actually took effect. A code that is unknown, expired, already used or
 * another brand's comes back `null` alongside the brand's default pricing — which is how a caller
 * tells the customer their code did not apply, rather than by reading an error.
 */
export type PricingTable = {
  promotionCode: string | null;
  renewalCouponCode: string | null;
  /** Pricings covering one student, in render order. */
  single: Pricing[];
  /** Pricings covering more than one student, in render order. */
  family: Pricing[];
};
