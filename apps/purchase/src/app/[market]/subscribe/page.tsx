import { Card, CardContent } from "@/components/ui/card";
import { requireMarket } from "@/lib/markets/require-market";

import { CouponRedemptionForm } from "./_components/coupon-redemption-form";

/**
 * Coupon redemption. First in the build order: the only flow with no payment
 * step at all — `PAID` at creation, `plan_price = 0` — so it can be
 * walked end to end without a charge while still exercising the whole vertical.
 *
 * The one flow with a title of its own; the paid flows use their "Secure
 * Checkout" lock as the page heading instead.
 *
 * UI only — see `CouponRedemptionForm`.
 */
export default async function SubscribePage({
  params,
}: PageProps<"/[market]/subscribe">) {
  const { market: code } = await params;

  return (
    <div className="mx-auto w-full max-w-3xl space-y-8">
      <h1 className="text-center text-4xl font-bold text-white">
        Subscription Form
      </h1>

      <Card>
        <CardContent>
          <CouponRedemptionForm market={await requireMarket(code)} />
        </CardContent>
      </Card>
    </div>
  );
}
