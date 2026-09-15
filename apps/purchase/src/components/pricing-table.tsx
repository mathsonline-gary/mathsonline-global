import {
  Field,
  FieldContent,
  FieldDescription,
  FieldLabel,
} from "@/components/ui/field";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";

import type { Pricing, PricingTable as Table } from "@/lib/pricing/types";
import { formatPrice } from "@/lib/utils/format-price";

/**
 * The plan grid every paid flow chooses from, ported from membership's
 * `orders/components/pricing_table.blade.php` and `pricing_card.blade.php`.
 *
 * Shared by all three forms because the grid is the same in each — what differs
 * between the flows is which pricings fill it, and that is the caller's query
 * to `getPricing`, not a variant here.
 *
 * Membership marks selection with a hand-rolled `.plan-option.active` class and
 * a jQuery click handler writing to a hidden input. This is a real radio group,
 * so keyboard selection and screen readers work without any of that, and it is
 * uncontrolled: the group owns its own selection, this component only picks
 * what it starts on.
 */

/**
 * Both groups render, always, in this order. The API guarantees both are
 * present and non-empty whatever was asked for, so there is nothing to branch
 * on — and it decides the order within each, which is why nothing is sorted
 * here.
 */
const GROUPS = [
  { key: "single", title: "Single Membership" },
  { key: "family", title: "Family Membership" },
] as const;

/**
 * What the customer is paying for — "per month", "for 12 months".
 *
 * Bonus intervals are folded into the total rather than announced: a year plus
 * two free months reads "for 14 months".
 *
 * ponytail: no plural rules beyond an `s`. Swap in `Intl.PluralRules` if a
 * market needs more, which none of the English-speaking ones do.
 */
function billingCaption(pricing: Pricing): string {
  const { interval, count, extraCount } = pricing.billingPeriod;

  if (pricing.recurring) {
    return count === 1 ? `per ${interval}` : `every ${count} ${interval}s`;
  }

  const total = count + extraCount;
  const period = `${total} ${total === 1 ? interval : `${interval}s`}`;

  // An instalment plan prices one payment, so the payments lead and the period qualifies them.
  return pricing.installmentCount > 0
    ? `${pricing.installmentCount} payments over ${period}`
    : `for ${period}`;
}

/**
 * The pricing to preselect: `preselectedPricingCode` matched case-insensitively against
 * both groups, so a deep link works whatever the link's case; failing that, the first
 * single pricing — membership's "please select a plan" state never existed here, because
 * something is always chosen. `undefined` only when there is no single pricing to fall
 * back to, which the API never actually sends.
 *
 * The `?plan_id=` search param every page reads this from carries a `Pricing.code` ("Y1"),
 * not a `Pricing.id` — membership named the param after its own column and the links in the
 * wild spell the code. Matching it against `id` would silently preselect nothing.
 */
function defaultCode(
  pricing: Table,
  preselectedPricingCode?: string,
): string | undefined {
  const wanted = preselectedPricingCode?.toUpperCase();
  const match = wanted
    ? [...pricing.single, ...pricing.family].find(
        (option) => option.code.toUpperCase() === wanted,
      )
    : undefined;

  return (match ?? pricing.single[0])?.code;
}

export function PricingTable({
  pricing,
  preselectedPricingCode,
}: {
  pricing: Table;
  preselectedPricingCode?: string;
}) {
  const selected = defaultCode(pricing, preselectedPricingCode);

  return (
    <RadioGroup
      // Keyed on the resolved selection: an uncontrolled group otherwise keeps whatever it
      // started on, so a link to a different `plan_id` would go ignored without a remount.
      key={selected}
      defaultValue={selected}
      className="gap-6"
      aria-label="Membership plan"
    >
      {GROUPS.map((group) => (
        <div key={group.key}>
          <h3 className="mb-4 font-semibold">{group.title}</h3>

          <div className="flex flex-col items-stretch gap-2 md:flex-row md:gap-4">
            {pricing[group.key].map((option) => (
              <div key={option.code} className="flex-1">
                <FieldLabel htmlFor={option.code} className="h-full">
                  <Field orientation="horizontal" className="h-full">
                    <FieldContent>
                      <div className="text-3xl font-bold">
                        {formatPrice(option.price, option.currency)}
                      </div>
                      <FieldDescription>
                        {billingCaption(option)}
                      </FieldDescription>

                      {/* Struck through only when there is a discount to see. */}
                      {option.priceOriginal > option.price ? (
                        <p className="text-muted-foreground line-through">
                          {formatPrice(option.priceOriginal, option.currency)}
                        </p>
                      ) : null}

                      {/* Shown as priced, never recomputed from the two above. */}
                      {option.priceSaved > 0 ? (
                        <p className="text-lg text-destructive">
                          Save {formatPrice(option.priceSaved, option.currency)}
                        </p>
                      ) : null}
                    </FieldContent>

                    <RadioGroupItem value={option.code} id={option.code} />
                  </Field>
                </FieldLabel>
              </div>
            ))}
          </div>
        </div>
      ))}
    </RadioGroup>
  );
}
