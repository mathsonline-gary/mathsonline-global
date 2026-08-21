import { LockIcon } from "lucide-react";

import { Card, CardContent } from "@/components/ui/card";

/**
 * The white card every paid flow's form sits in, with the "Secure Checkout"
 * lock pinned to its top-right from `md` up. Ported from the header membership
 * repeats verbatim in `new`, `renewal` and `gift`.
 *
 * That lock is the page's `<h1>`, as it is in membership — those three flows
 * render no other title. The flows that do have one (the coupon redemption's
 * "Subscription Form") own their heading and don't use this card.
 */
export function SecureCheckoutCard({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <Card className="relative">
      <CardContent className="grid gap-y-4">
        <h1 className="mb-4 flex items-center justify-center gap-2 text-2xl font-bold text-primary md:absolute md:top-6 md:right-8">
          <LockIcon className="size-5" />
          Secure Checkout
        </h1>

        {children}
      </CardContent>
    </Card>
  );
}
