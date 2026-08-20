import { OrderSidebar } from "@/components/order-sidebar";
import { PurchaseForm } from "@/components/purchase-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireMarket } from "@/lib/markets/require-market";

/**
 * Homeschool new order. Renamed off membership's `homeschool50` leaf — the `50`
 * named a discount rate that can change, and this is now the one segment both
 * homeschool flows nest under (`/renew` sits beneath it).
 *
 * Two membership paths collapse here, `/purchase/homeschool50` and
 * `/purchase/homeschool`, so the 301 map has two entries pointing at it.
 * Neither survives as an alias: one canonical path per flow.
 *
 * The form is the new order's; the half-price artwork and the homeschool
 * testimonial ride in the sidebar. UI only — see `PurchaseForm`.
 */
export default async function HomeschoolDiscountPage({
  params,
}: PageProps<"/[market]/homeschool-discount">) {
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
        <OrderSidebar market={market} variant="homeschool" />
      </div>
    </div>
  );
}
