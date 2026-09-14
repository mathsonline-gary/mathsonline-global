import { RenewalForm } from "@/components/renewal-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";
import { getPricing } from "@/lib/pricing/pricing.api";

/**
 * Renewal. No sidebar and a narrower column than the new order — membership
 * gives the reassurance cards only to the flows selling to someone who has
 * never bought before.
 *
 * UI only — see `RenewalForm`.
 */
export default async function RenewPage({
  params,
}: PageProps<"/[market]/renew">) {
  const { market: slug } = await params;
  const brand = await requireBrand(slug);
  const pricing = await getPricing(brand.code);

  return (
    <div className="mx-auto w-full max-w-3xl">
      <SecureCheckoutCard>
        <RenewalForm brand={brand} pricing={pricing} />
      </SecureCheckoutCard>
    </div>
  );
}
