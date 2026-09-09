import { HomeschoolBanner } from "@/components/homeschool-banner";
import { RenewalForm } from "@/components/renewal-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";

/**
 * Homeschool renewal. Was `/purchase/homeschool/renew` in membership; nests
 * under `homeschool-discount` so both homeschool flows share one parent segment.
 *
 * The renewal form with the half-price artwork above it — the only difference
 * from `/renew`, as it is in membership. UI only — see `RenewalForm`.
 */
export default async function HomeschoolDiscountRenewPage({
  params,
}: PageProps<"/[market]/homeschool-discount/renew">) {
  const { market: slug } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl space-y-4">
      <HomeschoolBanner />
      <SecureCheckoutCard>
        <RenewalForm brand={await requireBrand(slug)} />
      </SecureCheckoutCard>
    </div>
  );
}
