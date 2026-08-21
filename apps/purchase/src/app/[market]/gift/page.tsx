import { GiftForm } from "@/components/gift-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireMarket } from "@/lib/markets/require-market";

/**
 * Gift. The only flow paying via PayPal rather than Stripe, and the only one
 * that should 404 for some markets — a market with no PayPal configuration
 * cannot serve it. That gate needs per-market flow availability, which the
 * description does not carry, so it is not wired here yet.
 *
 * UI only — see `GiftForm`.
 */
export default async function GiftPage({
  params,
}: PageProps<"/[market]/gift">) {
  const { market: code } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl">
      <SecureCheckoutCard>
        <GiftForm market={await requireMarket(code)} />
      </SecureCheckoutCard>
    </div>
  );
}
