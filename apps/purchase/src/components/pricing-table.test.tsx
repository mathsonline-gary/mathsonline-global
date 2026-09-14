import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import { PricingTable } from "./pricing-table";

import type { Pricing } from "@/lib/pricing/types";

function pricing(overrides: Partial<Pricing> = {}): Pricing {
  return {
    code: "M1",
    price: 19.97,
    priceOriginal: 19.97,
    priceSaved: 0,
    currency: "AUD",
    recurring: true,
    studentLimit: 1,
    billingPeriod: { interval: "month", count: 1, extraCount: 0 },
    installmentCount: 0,
    ...overrides,
  };
}

function table(
  single: Pricing[],
  family: Pricing[] = [pricing({ code: "M3" })],
) {
  return {
    promotionCode: null,
    renewalCouponCode: null,
    single,
    family,
  };
}

describe("PricingTable", () => {
  it("offers every pricing in both groups as a radio, so one can be chosen by keyboard", () => {
    render(
      <PricingTable
        pricing={table(
          [pricing({ code: "M1" }), pricing({ code: "Y1" })],
          [pricing({ code: "M3" })],
        )}
      />,
    );

    expect(screen.getAllByRole("radio")).toHaveLength(3);
  });

  it("captions a recurring pricing by its period and a one-off by its length", () => {
    render(
      <PricingTable
        pricing={table(
          [
            pricing({ code: "M1" }),
            pricing({
              code: "Y1",
              recurring: false,
              billingPeriod: { interval: "month", count: 12, extraCount: 0 },
            }),
          ],
          // A yearly family pricing, so neither caption under test appears twice.
          [
            pricing({
              code: "Y3",
              billingPeriod: { interval: "year", count: 1, extraCount: 0 },
            }),
          ],
        )}
      />,
    );

    expect(screen.getByText("per month")).toBeInTheDocument();
    expect(screen.getByText("for 12 months")).toBeInTheDocument();
  });

  it("folds bonus intervals into the length and names the installments", () => {
    render(
      <PricingTable
        pricing={table([
          pricing({
            recurring: false,
            billingPeriod: { interval: "month", count: 12, extraCount: 2 },
            installmentCount: 4,
          }),
        ])}
      />,
    );

    expect(
      screen.getByText("for 14 months, in 4 payments"),
    ).toBeInTheDocument();
  });

  it("shows what the API priced as saved rather than the difference it could derive", () => {
    render(
      <PricingTable
        pricing={table([
          pricing({ price: 197, priceOriginal: 239.64, priceSaved: 40 }),
        ])}
      />,
    );

    expect(screen.getByText("Save $40")).toBeInTheDocument();
    expect(screen.getByText("$239.64")).toBeInTheDocument();
  });

  it("leaves the original price and the saving out when nothing is discounted", () => {
    render(<PricingTable pricing={table([pricing()])} />);

    expect(screen.queryByText(/^Save/)).not.toBeInTheDocument();
    expect(screen.getAllByText("$19.97")).toHaveLength(2); // the two groups' prices, no struck original
  });

  it("draws each pricing in its own currency, not one the table picked", () => {
    render(
      <PricingTable
        pricing={table(
          [pricing({ currency: "USD", price: 20 })],
          [pricing({ code: "M3", currency: "GBP", price: 30 })],
        )}
      />,
    );

    expect(screen.getByText("$20")).toBeInTheDocument();
    expect(screen.getByText("£30")).toBeInTheDocument();
  });
});
