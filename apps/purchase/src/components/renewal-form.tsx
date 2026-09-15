import { PricingTable } from "@/components/pricing-table";
import { Recaptcha } from "@/components/recaptcha";
import { StepHeading } from "@/components/step-heading";
import { TermsAgreementField } from "@/components/terms-agreement-field";
import { Button } from "@/components/ui/button";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import type { Brand } from "@/lib/brands/types";
import type { PricingTable as Table } from "@/lib/pricing/types";

/**
 * The renewal form, ported from membership's `orders/create/renewal.blade.php`,
 * and shared with the homeschool renewal — the two differ only in the banner
 * above the card.
 *
 * Email comes first and the plan second, the reverse of the new order: a
 * renewing customer is identified by the email membership already holds and
 * validates server-side before the order is worth building. That check is
 * behaviour, so the field is inert.
 *
 * Markup only — see `PurchaseForm` for what the forms deliberately don't do.
 */
export function RenewalForm({
  brand,
  pricing,
  preselectedPricingCode,
}: {
  brand: Brand;
  pricing: Table;
  preselectedPricingCode?: string;
}) {
  return (
    <form className="space-y-4">
      <section>
        <StepHeading step={1}>Enter Your Email</StepHeading>

        <Field>
          <FieldLabel htmlFor="email">Email address</FieldLabel>
          <Input id="email" name="email" type="email" autoComplete="email" />
        </Field>
      </section>

      <section>
        <StepHeading step={2}>Choose Membership</StepHeading>
        <PricingTable
          pricing={pricing}
          preselectedPricingCode={preselectedPricingCode}
        />
      </section>

      <TermsAgreementField marketingWebsite={brand.marketingWebsite} />

      <Recaptcha siteKey={brand.googleRecaptchaSiteKey} />

      <Button type="submit" size="lg" className="w-full px-8">
        Continue
      </Button>
    </form>
  );
}
