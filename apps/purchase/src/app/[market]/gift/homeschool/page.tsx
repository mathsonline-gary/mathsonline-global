import { GiftForm } from "@/components/gift-form";
import { HomeschoolBanner } from "@/components/homeschool-banner";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";

/**
 * Homeschool gift. The gift form with the half-price artwork above it — the
 * discount is in the plans, not the fields. UI only — see `GiftForm`.
 */
export default async function GiftHomeschoolPage({
  params,
}: PageProps<"/[market]/gift/homeschool">) {
  const { market: slug } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl space-y-4">
      <HomeschoolBanner />
      <SecureCheckoutCard>
        <GiftForm brand={await requireBrand(slug)} />
      </SecureCheckoutCard>
    </div>
  );
}
