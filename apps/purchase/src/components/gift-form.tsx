import { AddressFields } from "@/components/address-fields";
import { PlanSelector } from "@/components/plan-selector";
import { RecaptchaPlaceholder } from "@/components/recaptcha-placeholder";
import { StepHeading } from "@/components/step-heading";
import { TermsAgreementField } from "@/components/terms-agreement-field";
import { Button } from "@/components/ui/button";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import type { Market } from "@/lib/markets/types";

/**
 * The gift form, ported from membership's `orders/create/gift.blade.php`, and
 * shared with the homeschool gift.
 *
 * Step 2 collects the *recipient's* details, not the buyer's — the one flow
 * where the person filling the form is not the person being enrolled.
 *
 * Its submit button says "Continue Process" because it does not end the
 * purchase: gift pays through PayPal rather than Stripe, and membership hands
 * off by POSTing a hidden form to PayPal's `webscr` endpoint.
 * None of that hand-off is here.
 *
 * Markup only — see `PurchaseForm` for what the forms deliberately don't do.
 */
export function GiftForm({ market }: { market: Market }) {
  return (
    <form className="space-y-4">
      <section>
        <StepHeading step={1}>Choose Membership</StepHeading>
        <PlanSelector currency={market.currency} />
      </section>

      <section className="space-y-4">
        <StepHeading step={2}>Enter Recipient Details</StepHeading>

        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor="first-name">First name</FieldLabel>
            <Input id="first-name" name="first_name" />
          </Field>

          <Field>
            <FieldLabel htmlFor="last-name">Last name</FieldLabel>
            <Input id="last-name" name="last_name" />
          </Field>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor="email">Email address</FieldLabel>
            <Input id="email" name="email" type="email" />
          </Field>

          <Field>
            <FieldLabel htmlFor="email-confirmation">Confirm email</FieldLabel>
            <Input
              id="email-confirmation"
              name="email_confirmation"
              type="email"
            />
          </Field>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor="phone">Phone number</FieldLabel>
            <Input id="phone" name="phone" type="tel" />
          </Field>
        </div>

        <AddressFields />
      </section>

      <TermsAgreementField marketingWebsite={market.marketingWebsite} />

      <RecaptchaPlaceholder />

      <Button type="submit" size="lg" className="w-full px-8">
        Continue Process
      </Button>
    </form>
  );
}
