import { OrderSidebar } from "@/components/order-sidebar";
import { PurchaseForm } from "@/components/purchase-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";
import { getPricing } from "@/lib/pricing/pricing.api";
import { readSearchParam } from "@/lib/utils/read-search-param";

/**
 * Homeschool new order. Renamed off membership's `homeschool50` leaf — the `50`
 * named a discount rate that can change, and this is now the one segment both
 * homeschool flows nest under (`/renew` sits beneath it).
 *
 * Two membership paths collapse here, `/purchase/homeschool50` and
 * `/purchase/homeschool` — two 301 entries, no aliases: one canonical path per
 * flow.
 *
 * The form is the new order's; the half-price artwork and the homeschool
 * testimonial ride in the sidebar. UI only — see `PurchaseForm`.
 */
export default async function HomeschoolDiscountPage({
  params,
  searchParams,
}: PageProps<"/[market]/homeschool-discount">) {
  const { market: slug } = await params;
  const { plan_id: preselectedPricingCode } = await searchParams;
  const brand = await requireBrand(slug);
  const pricing = await getPricing(brand.code, { homeschool: true });

  return (
    <div className="grid gap-6 lg:grid-cols-5">
      <section className="space-y-4 lg:col-span-3">
        <SecureCheckoutCard>
          <PurchaseForm
            brand={brand}
            pricing={pricing}
            preselectedPricingCode={readSearchParam(preselectedPricingCode)}
          />
        </SecureCheckoutCard>
      </section>

      <div className="lg:col-span-2">
        <OrderSidebar brand={brand} variant="homeschool" />
      </div>
    </div>
  );
}
