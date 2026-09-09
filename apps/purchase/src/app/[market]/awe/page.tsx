import { OrderSidebar } from "@/components/order-sidebar";
import { PurchaseForm } from "@/components/purchase-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";

/**
 * AWE order. Membership serves this from the same blade as the new order behind
 * an `$awe` flag, and so does this page: the same form, with AWE's testimonial
 * and the AWE Discounts logo in the sidebar.
 *
 * UI only — see `PurchaseForm`.
 */
export default async function AwePage({ params }: PageProps<"/[market]/awe">) {
  const { market: slug } = await params;
  const brand = await requireBrand(slug);

  return (
    <div className="grid gap-6 lg:grid-cols-5">
      <section className="space-y-4 lg:col-span-3">
        <SecureCheckoutCard>
          <PurchaseForm brand={brand} />
        </SecureCheckoutCard>
      </section>

      <div className="lg:col-span-2">
        <OrderSidebar brand={brand} variant="awe" />
      </div>
    </div>
  );
}
