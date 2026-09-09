import { GiftForm } from "@/components/gift-form";
import { SecureCheckoutCard } from "@/components/secure-checkout-card";
import { requireBrand } from "@/lib/brands/require-brand";

/**
 * Gift. The only flow paying via PayPal rather than Stripe, and the only one
 * that should 404 for some brands — a brand with no PayPal configuration
 * cannot serve it. That gate needs per-brand flow availability, which the
 * description does not carry, so it is not wired here yet.
 *
 * UI only — see `GiftForm`.
 */
export default async function GiftPage({
  params,
}: PageProps<"/[market]/gift">) {
  const { market: slug } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl">
      <SecureCheckoutCard>
        <GiftForm brand={await requireBrand(slug)} />
      </SecureCheckoutCard>
    </div>
  );
}
