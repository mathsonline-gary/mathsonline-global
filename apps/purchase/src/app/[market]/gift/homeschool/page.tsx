import { GiftForm } from "@/components/gift-form";
import { HomeschoolBanner } from "@/components/homeschool-banner";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";
import { getPricing } from "@/lib/pricing/pricing.api";
import { readSearchParam } from "@/lib/utils/read-search-param";

/**
 * Homeschool gift. The gift form with the half-price artwork above it — the
 * discount is in the plans, not the fields. UI only — see `GiftForm`.
 */
export default async function GiftHomeschoolPage({
  params,
  searchParams,
}: PageProps<"/[market]/gift/homeschool">) {
  const { market: slug } = await params;
  const { plan_id: preselectedPricingCode } = await searchParams;
  const brand = await requireBrand(slug);
  const pricing = await getPricing(brand.code, { homeschool: true });

  return (
    <div className="mx-auto w-full max-w-3xl space-y-4">
      <HomeschoolBanner />
      <SecureCheckoutCard>
        <GiftForm
          brand={brand}
          pricing={pricing}
          preselectedPricingCode={readSearchParam(preselectedPricingCode)}
        />
      </SecureCheckoutCard>
    </div>
  );
}
