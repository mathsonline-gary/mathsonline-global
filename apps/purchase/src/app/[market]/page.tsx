import { OrderSidebar } from "@/components/order-sidebar";
import { PurchaseForm } from "@/components/purchase-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";
import { getPricing } from "@/lib/pricing/pricing.api";

/**
 * New order. `/{market}` is the flow itself, not a brand landing page:
 * membership's redundant `/purchase` segment is gone, and nothing neutral sits
 * above it.
 *
 * The 3/2 split is membership's `orders/create/new.blade.php`: the form on the
 * left, the reassurance column on the right, stacked below `lg`.
 *
 * UI only so far — see `PurchaseForm` for what the form deliberately does not
 * do yet.
 */
export default async function NewOrderPage({
  params,
  searchParams,
}: PageProps<"/[market]">) {
  const { market: slug } = await params;
  const { tr_id: promotionCode } = await searchParams;
  const brand = await requireBrand(slug);
  // `tr_id` is membership's name for the promotion code, kept because the marketing links that
  // carry it are already out there. Anything unknown fails soft into the default pricing.
  const pricing = await getPricing(brand.code, {
    promotionCode: typeof promotionCode === "string" ? promotionCode : undefined,
  });

  return (
    <div className="grid gap-6 lg:grid-cols-5">
      <section className="space-y-4 lg:col-span-3">
        <SecureCheckoutCard>
          <PurchaseForm brand={brand} pricing={pricing} />
        </SecureCheckoutCard>
      </section>

      <div className="lg:col-span-2">
        <OrderSidebar brand={brand} />
      </div>
    </div>
  );
}
