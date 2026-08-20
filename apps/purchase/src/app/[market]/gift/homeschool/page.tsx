import { GiftForm } from "@/components/gift-form";
import { HomeschoolBanner } from "@/components/homeschool-banner";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireMarket } from "@/lib/markets/require-market";

/**
 * Homeschool gift. The gift form with the half-price artwork above it — the
 * discount is in the plans, not the fields. UI only — see `GiftForm`.
 */
export default async function GiftHomeschoolPage({
  params,
}: PageProps<"/[market]/gift/homeschool">) {
  const { market: code } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl space-y-4">
      <HomeschoolBanner />
      <SecureCheckoutCard>
        <GiftForm market={await requireMarket(code)} />
      </SecureCheckoutCard>
    </div>
  );
}
