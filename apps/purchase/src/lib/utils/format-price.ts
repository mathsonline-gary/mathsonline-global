/**
 * Money as the customer sees it.
 *
 * Money is never redenominated here — the market's currency only decides which
 * symbol is drawn around a figure membership already priced. The locale is pinned rather than taken from the browser, so
 * the server and the client render the same string and hydration matches.
 *
 * Whole amounts lose their `.00`, as they do in membership's
 * `str_replace('.00', '', …)`.
 *
 * The only amounts on these pages are placeholders today; this moves into a
 * real pricing module when the plans read lands.
 */
export function formatPrice(amount: number, currency: string): string {
  return new Intl.NumberFormat("en", {
    style: "currency",
    currency,
    currencyDisplay: "narrowSymbol",
    minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
  }).format(amount);
}
