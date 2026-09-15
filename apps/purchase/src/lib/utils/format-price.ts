/**
 * Money as the customer sees it.
 *
 * Never redenominated — the currency only picks the symbol drawn around a figure
 * membership already priced, and it is the amount's own, not the brand's, which
 * is only a default.
 *
 * The locale is pinned rather than taken from the browser, so server and client
 * render the same string and hydration matches. Whole amounts lose their `.00`,
 * as they do in membership's `str_replace('.00', '', …)`.
 */
export function formatPrice(amount: number, currency: string): string {
  return new Intl.NumberFormat("en", {
    style: "currency",
    currency,
    currencyDisplay: "narrowSymbol",
    minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
  }).format(amount);
}
