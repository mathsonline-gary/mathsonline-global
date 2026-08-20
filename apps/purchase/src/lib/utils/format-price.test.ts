import { describe, expect, it } from "vitest";

import { formatPrice } from "./format-price";

describe("formatPrice", () => {
  it("drops the cents from a whole amount, as membership does", () => {
    expect(formatPrice(197, "AUD")).toBe("$197");
  });

  it("keeps both decimals on a fractional amount", () => {
    expect(formatPrice(19.97, "AUD")).toBe("$19.97");
  });

  it("draws the narrow symbol of the market's currency", () => {
    expect(formatPrice(19.97, "USD")).toBe("$19.97");
    expect(formatPrice(14.97, "GBP")).toBe("£14.97");
  });

  it("does not redenominate — only the symbol changes", () => {
    const amounts = ["AUD", "USD", "GBP"].map((currency) =>
      formatPrice(197, currency).replace(/^\D+/, ""),
    );

    expect(new Set(amounts)).toEqual(new Set(["197"]));
  });

  it("pins the locale, so the server and the client render the same string", () => {
    // A grouping separator and a decimal comma would both differ under de-DE. Neither may appear.
    expect(formatPrice(1234.5, "AUD")).toBe("$1,234.50");
  });
});
