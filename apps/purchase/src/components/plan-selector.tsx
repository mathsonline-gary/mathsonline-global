"use client";

import { useState } from "react";

import {
  Field,
  FieldContent,
  FieldDescription,
  FieldLabel,
} from "@/components/ui/field";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";

import { formatPrice } from "@/lib/utils/format-price";

/**
 * Step 1 of the new-order form — the plan grid, ported from membership's
 * `orders/components/pricing_table.blade.php` and `pricing_card.blade.php`.
 *
 * UI only. The plans below are placeholders so the layout can be seen: real
 * plans need a plans endpoint the description does not describe yet, and the
 * selected plan will become a form field rather than the local state it holds
 * here.
 *
 * Membership marks selection with a hand-rolled `.plan-option.active` class and
 * a jQuery click handler writing to a hidden input. This is a real radio group,
 * so keyboard selection and screen readers work without any of that.
 */
type PlanOption = {
  id: string;
  price: number;
  /**
   * Pre-discount price. Not shown itself — it only exists so the card can say
   * how much the customer saves.
   */
  priceOriginal?: number;
  /** What the customer is paying for — "per month", "for 12 months". */
  caption: string;
};

type PlanGroup = {
  title: string;
  options: PlanOption[];
};

const PLACEHOLDER_PLAN_GROUPS: PlanGroup[] = [
  {
    title: "Single Membership",
    options: [
      { id: "single-monthly", price: 19.97, caption: "per month" },
      {
        id: "single-yearly",
        price: 197,
        priceOriginal: 239.64,
        caption: "for 12 months",
      },
    ],
  },
  {
    title: "Family Membership",
    options: [
      { id: "family-monthly", price: 29.97, caption: "per month" },
      {
        id: "family-yearly",
        price: 297,
        priceOriginal: 359.64,
        caption: "for 12 months",
      },
    ],
  },
];

export function PlanSelector({ currency }: { currency: string }) {
  const [planId, setPlanId] = useState<string | null>(null);

  return (
    <RadioGroup
      value={planId}
      onValueChange={(value) => setPlanId(value as string)}
      className="gap-6"
      aria-label="Membership plan"
    >
      {PLACEHOLDER_PLAN_GROUPS.map((group) => (
        <div key={group.title}>
          <h3 className="mb-4 font-semibold">{group.title}</h3>

          <div className="flex flex-col items-stretch gap-2 md:flex-row md:gap-4">
            {group.options.map((option) => {
              const saved = option.priceOriginal
                ? option.priceOriginal - option.price
                : 0;

              return (
                <div key={option.id} className="flex-1">
                  <FieldLabel htmlFor={option.id} className="h-full">
                    <Field orientation="horizontal" className="h-full">
                      <FieldContent>
                        <div className="text-3xl font-bold">
                          {formatPrice(option.price, currency)}
                        </div>
                        <FieldDescription>{option.caption}</FieldDescription>
                        {saved > 0 ? (
                          <p className="text-lg text-destructive">
                            Save {formatPrice(Math.floor(saved), currency)}
                          </p>
                        ) : null}
                      </FieldContent>
                      <RadioGroupItem value={option.id} id={option.id} />
                    </Field>
                  </FieldLabel>
                </div>
              );
            })}
          </div>
        </div>
      ))}
    </RadioGroup>
  );
}
