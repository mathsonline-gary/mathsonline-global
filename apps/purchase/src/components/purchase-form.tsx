import { PlanSelector } from "@/components/plan-selector";
import { RecaptchaPlaceholder } from "@/components/recaptcha-placeholder";
import { StepHeading } from "@/components/step-heading";
import { TermsAgreementField } from "@/components/terms-agreement-field";
import { Button } from "@/components/ui/button";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import type { Market } from "@/lib/markets/types";

/**
 * The new-order form, ported from membership's `orders/create/new.blade.php`:
 * two numbered steps — choose a membership, then enter your details.
 *
 * Shared by the three flows membership serves from that same blade: the plain
 * new order, the homeschool half-price order and the AWE order. What differs
 * between them is the sidebar and the plans, never the form.
 *
 * Membership leaves its plan grid outside the `<form>` and posts the choice
 * through a hidden input a click handler writes to. Both steps sit inside the
 * form here, because the radio group already is the field.
 *
 * Markup only. There is no `onSubmit`, no validation and no submission: the
 * fields are uncontrolled, and the `react-hook-form` + `zod` schema, the
 * reCAPTCHA widget and the POST to membership all land with the flow's logic.
 */
export function PurchaseForm({ market }: { market: Market }) {
  return (
    <form className="space-y-4">
      <section>
        <StepHeading step={1}>Choose Membership</StepHeading>
        <PlanSelector currency={market.currency} />
      </section>

      <section>
        <StepHeading step={2}>Enter Your Details</StepHeading>

        <FieldGroup className="gap-4">
          <div className="grid gap-4 md:grid-cols-2">
            <Field>
              <FieldLabel htmlFor="first-name">First name</FieldLabel>
              <Input
                id="first-name"
                name="first_name"
                autoComplete="given-name"
              />
            </Field>

            <Field>
              <FieldLabel htmlFor="last-name">Last name</FieldLabel>
              <Input
                id="last-name"
                name="last_name"
                autoComplete="family-name"
              />
            </Field>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <Field>
              <FieldLabel htmlFor="email">Email address</FieldLabel>
              <Input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
              />
            </Field>

            <Field>
              <FieldLabel htmlFor="email-confirmation">
                Confirm email
              </FieldLabel>
              <Input
                id="email-confirmation"
                name="email_confirmation"
                type="email"
              />
            </Field>
          </div>
        </FieldGroup>
      </section>

      <TermsAgreementField marketingWebsite={market.marketingWebsite} />

      <RecaptchaPlaceholder />

      <Button type="submit" size="lg" className="w-full px-8">
        Register
      </Button>
    </form>
  );
}
