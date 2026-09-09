import { RenewalForm } from "@/components/renewal-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";

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

  return (
    <div className="mx-auto w-full max-w-3xl">
      <SecureCheckoutCard>
        <RenewalForm brand={await requireBrand(slug)} />
      </SecureCheckoutCard>
    </div>
  );
}
