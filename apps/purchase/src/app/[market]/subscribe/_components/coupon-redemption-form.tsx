import { AddressFields } from "@/components/address-fields";
import { StepHeading } from "@/components/step-heading";
import { RecaptchaPlaceholder } from "@/components/recaptcha-placeholder";
import { TermsAgreementField } from "@/components/terms-agreement-field";
import { Button } from "@/components/ui/button";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import type { Market } from "@/lib/markets/types";

/**
 * The coupon-redemption form, ported from membership's
 * `orders/create/coupon_redemption.blade.php`.
 *
 * No "Secure Checkout" lock and no plan grid, because nothing is charged: the
 * coupon is the whole transaction, which is why this flow is built first. It
 * asks for a full postal address where the paid flows don't.
 *
 * Markup only — see `PurchaseForm` for what the forms deliberately don't do.
 */
export function CouponRedemptionForm({ market }: { market: Market }) {
  return (
    <form className="space-y-4">
      <section>
        <StepHeading step={1}>Enter Coupon Code</StepHeading>

        <Field>
          <FieldLabel htmlFor="coupon-code">Coupon code</FieldLabel>
          <Input id="coupon-code" name="coupon_code" maxLength={8} />
        </Field>
      </section>

      <section className="space-y-4">
        <StepHeading step={2}>Enter Your Details</StepHeading>

        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor="first-name">
              Parent / Carer&apos;s first name
            </FieldLabel>
            <Input
              id="first-name"
              name="first_name"
              autoComplete="given-name"
            />
          </Field>

          <Field>
            <FieldLabel htmlFor="last-name">
              Parent / Carer&apos;s last name
            </FieldLabel>
            <Input id="last-name" name="last_name" autoComplete="family-name" />
          </Field>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor="email">Email address</FieldLabel>
            <Input id="email" name="email" type="email" autoComplete="email" />
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

        <Field>
          <FieldLabel htmlFor="phone">Phone number</FieldLabel>
          <Input id="phone" name="phone" type="tel" autoComplete="tel" />
        </Field>

        <AddressFields />
      </section>

      <TermsAgreementField marketingWebsite={market.marketingWebsite} />

      <RecaptchaPlaceholder />

      <Button type="submit" size="lg" className="w-full px-8">
        Redeem Membership
      </Button>
    </form>
  );
}
