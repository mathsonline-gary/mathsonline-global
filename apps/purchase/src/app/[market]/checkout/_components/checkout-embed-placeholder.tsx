import { Card, CardContent } from "@/components/ui/card";

/**
 * Where Stripe Embedded Checkout mounts.
 *
 * Membership's `orders/checkout/show.blade.php` switches on the order status
 * here and renders a paid / cancelled / not-found panel instead of the form
 * when the order isn't `READY`. That switch needs the order, so this page shows
 * only the ready frame — the one state that is a payment form.
 *
 * The embed itself will be the one `'use client'` leaf on the page; everything
 * around it stays a Server Component.
 */
export function CheckoutEmbedPlaceholder() {
  return (
    <Card>
      <CardContent>
        <div className="flex h-96 items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground">
          Stripe Embedded Checkout
        </div>
      </CardContent>
    </Card>
  );
}
