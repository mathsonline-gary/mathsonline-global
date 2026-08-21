import { OrderSidebar } from "@/components/order-sidebar";
import { PurchaseForm } from "@/components/purchase-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireMarket } from "@/lib/markets/require-market";

/**
 * AWE order. Membership serves this from the same blade as the new order behind
 * an `$awe` flag, and so does this page: the same form, with AWE's testimonial
 * and the AWE Discounts logo in the sidebar.
 *
 * UI only — see `PurchaseForm`.
 */
export default async function AwePage({ params }: PageProps<"/[market]/awe">) {
  const { market: code } = await params;
  const market = await requireMarket(code);

  return (
    <div className="grid gap-6 lg:grid-cols-5">
      <section className="space-y-4 lg:col-span-3">
        <SecureCheckoutCard>
          <PurchaseForm market={market} />
        </SecureCheckoutCard>
      </section>

      <div className="lg:col-span-2">
        <OrderSidebar market={market} variant="awe" />
      </div>
    </div>
  );
}
