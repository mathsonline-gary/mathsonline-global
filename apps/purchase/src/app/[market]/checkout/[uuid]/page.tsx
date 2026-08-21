import { requireMarket } from "@/lib/markets/require-market";

import { CheckoutEmbedPlaceholder } from "../_components/checkout-embed-placeholder";

/**
 * Stripe Embedded Checkout. The order uuid is a path segment rather than
 * membership's `?oid=`, so a missing order is a routing 404 instead of a runtime
 * branch. The embed itself is the one `'use client'` leaf; this page stays a
 * Server Component.
 *
 * UI only — see `CheckoutEmbedPlaceholder`. Nothing reads the uuid yet, because
 * nothing reads the order.
 */
export default async function CheckoutPage({
  params,
}: PageProps<"/[market]/checkout/[uuid]">) {
  const { market: code } = await params;

  await requireMarket(code);

  return <CheckoutEmbedPlaceholder />;
}
