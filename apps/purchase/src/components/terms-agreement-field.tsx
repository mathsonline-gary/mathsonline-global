import { Checkbox } from "@/components/ui/checkbox";
import { Field, FieldLabel } from "@/components/ui/field";

/**
 * The terms checkbox every flow ends with.
 *
 * Membership links `/termsconditions` relative, because the form is served from
 * the same host as the marketing site. This application is its own host, so the
 * link has to be absolute against the market's marketing website.
 */
export function TermsAgreementField({
  marketingWebsite,
}: {
  marketingWebsite: string;
}) {
  return (
    <Field orientation="horizontal" className="py-4">
      <Checkbox id="agreement" name="agreement" />
      <FieldLabel htmlFor="agreement" className="font-normal">
        I have read and agree to the{" "}
        <a
          href={`${marketingWebsite}/termsconditions`}
          target="_blank"
          rel="noreferrer"
          className="text-primary underline underline-offset-4"
        >
          Terms and Conditions
        </a>
      </FieldLabel>
    </Field>
  );
}
