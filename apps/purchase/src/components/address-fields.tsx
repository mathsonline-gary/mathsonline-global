import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

/**
 * The postal address block shared by the coupon-redemption and gift forms —
 * the two flows that post something physical or need a billing address.
 *
 * Membership ships every line but the first with `display: none` and reveals
 * them from its Google Places autocomplete callback. They are all visible here:
 * hiding them is behaviour, and there is no autocomplete to reveal them yet.
 *
 * The country list is a placeholder. Membership renders every ISO country from
 * its own table, which the description offers no read for. Base UI resolves the
 * trigger's label from `items`, so the options are data rather than inline
 * children — without it the trigger would show the raw country code.
 */
const PLACEHOLDER_COUNTRIES = [
  { value: "AU", label: "Australia" },
  { value: "NZ", label: "New Zealand" },
  { value: "GB", label: "United Kingdom" },
  { value: "US", label: "United States" },
];

export function AddressFields() {
  return (
    <>
      <Field>
        <FieldLabel htmlFor="address-1">Address line 1</FieldLabel>
        <Input id="address-1" name="address_1" autoComplete="address-line1" />
      </Field>

      <Field>
        <FieldLabel htmlFor="address-2">Address line 2 (optional)</FieldLabel>
        <Input id="address-2" name="address_2" autoComplete="address-line2" />
      </Field>

      <div className="grid gap-4 md:grid-cols-2">
        <Field>
          <FieldLabel htmlFor="city">City</FieldLabel>
          <Input id="city" name="city" autoComplete="address-level2" />
        </Field>

        <Field>
          <FieldLabel htmlFor="state">State</FieldLabel>
          <Input id="state" name="state" autoComplete="address-level1" />
        </Field>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Field>
          <FieldLabel htmlFor="postal-code">Zip</FieldLabel>
          <Input
            id="postal-code"
            name="postal_code"
            autoComplete="postal-code"
          />
        </Field>

        <Field>
          <FieldLabel htmlFor="country">Country</FieldLabel>
          <Select name="country" items={PLACEHOLDER_COUNTRIES}>
            <SelectTrigger id="country" className="w-full">
              <SelectValue placeholder="Select country" />
            </SelectTrigger>
            <SelectContent>
              {PLACEHOLDER_COUNTRIES.map((country) => (
                <SelectItem key={country.value} value={country.value}>
                  {country.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </Field>
      </div>
    </>
  );
}
