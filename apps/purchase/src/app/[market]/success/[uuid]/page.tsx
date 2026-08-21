import Image from "next/image";

import { Card, CardContent } from "@/components/ui/card";
import { requireMarket } from "@/lib/markets/require-market";

/**
 * Order success. Every flow terminates here, which is why it lands alongside
 * the first one built. It deliberately does not gate on payment status — the Stripe
 * webhook lags the redirect — matching membership's current behaviour.
 *
 * Membership switches its title, body and footnote on the order type — trial,
 * renewal, gift, or a plain purchase. That needs the order, and nothing reads
 * the order yet, so the uuid goes unread and this is the plain-purchase
 * wording. Membership also names the customer's email address here; that is
 * the order's too, so the sentence drops it.
 *
 * UI only.
 */
export default async function SuccessPage({
  params,
}: PageProps<"/[market]/success/[uuid]">) {
  const { market: code } = await params;
  const market = await requireMarket(code);

  return (
    <div className="mx-auto w-full max-w-3xl">
      <Card>
        <CardContent className="space-y-4">
          <h1 className="mb-8 text-center text-4xl font-bold">
            Thank you for purchasing a MathsOnline membership
          </h1>

          <p>
            We have just sent an email to you which you should receive in the
            next few minutes. In this email are instructions on activating your
            account and getting started on the program. Follow these simple
            instructions and you should be up and running in a few minutes.
          </p>

          <p className="mb-8">
            We thank you for giving us the opportunity to be involved in helping
            you and your family with their math.
          </p>

          <p>
            Kind regards,
            <br />
            Pat
          </p>

          <Image
            src="/pat-signature.jpg"
            alt="Pat Murray"
            width={253}
            height={103}
            className="mb-4 h-auto w-48"
          />

          <p className="mb-8">
            Pat Murray on behalf of all the team at MathsOnline
          </p>

          <div className="border-t border-dashed pt-4">
            <em>
              <strong>The activation email has been sent.</strong> If you do not
              receive the email within ten minutes, please check your spam
              folder for an email from MathsOnline. If you cannot locate this
              email please contact us at{" "}
              <a
                href={`mailto:${market.infoEmail}`}
                className="text-primary underline underline-offset-4"
              >
                {market.infoEmail}
              </a>{" "}
              to request for it to be re-sent to you.
            </em>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
