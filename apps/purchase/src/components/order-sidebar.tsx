import { LockIcon, ShieldHalfIcon } from "lucide-react";
import Image from "next/image";

import { HomeschoolBanner } from "@/components/homeschool-banner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import type { Market } from "@/lib/markets/types";

/**
 * The reassurance column beside the purchase form — testimonial, guarantee,
 * security — ported from membership's `orders/components/sidebar.blade.php`,
 * including its three variants.
 *
 * `homeschool` puts the half-price artwork above the quote and swaps in the
 * homeschool testimonial; `awe` swaps the quote and adds the AWE Discounts
 * logo. Everything below the quote is the same in all three.
 *
 * The cards are file-private, as they are in membership: the sidebar is the
 * only thing that has ever wanted a testimonial or a guarantee banner. Lift one
 * out the day a second caller appears, not before.
 */
export type OrderSidebarVariant = "default" | "homeschool" | "awe";

type Testimonial = {
  /** One paragraph per entry, rendered in order. */
  quote: string[];
  name: string;
  /** Where the reviewer is from, shown under their name. */
  location: string;
  /** Square headshot under `public/testimonials/`. */
  avatar: string;
};

/**
 * The customer quotes, hard-coded rather than fetched. They are marketing copy,
 * not market configuration — not on the v2 market payload's allowlist, and
 * changing on a copywriter's timescale — so they ship with the component.
 *
 * Membership keys them on `brands.id`; here they are keyed on the market code,
 * which is the only market identity on the wire. Each ported quote is matched to
 * its market by the reviewer's own stated location.
 *
 * Coverage is ragged in membership too — only some markets have a quote for
 * some variants — so a missing one renders nothing. Don't invent copy to fill
 * a gap.
 */
const TESTIMONIALS: Record<OrderSidebarVariant, Record<string, Testimonial>> = {
  default: {
    au: {
      quote: [
        '"Hi my name is Nate, I love MathOnline because it is simple to use and the video tells you what you going to be doing and the next exercise.',
        'With MathOnline I am two years ahead and mum can go and check, to see what percentage I am working at. I am currently working at 90%."',
      ],
      name: "Nate Gaze (Year 5)",
      location: "Baldivis WA",
      avatar: "/testimonials/au.jpg",
    },
    uk: {
      quote: [
        "I had my daughter Katie tutored, but saw that she was not learning and finding maths confusing, so I started her off at MathsOnline. She was 11 years old, but started in Year 5.",
        "Amazingly, she shot right up to KS3 within 6 months and then did her math GCSE at the age of 13!",
        "I would truly recommend this program to everyone!",
      ],
      name: "Diana Frances Wilder",
      location: "UK",
      avatar: "/testimonials/uk.jpg",
    },
    us: {
      quote: [
        "Our oldest graduated last year and even though he only used the program for less than two years his math improved so much that he did great on his SAT test and went right into regular college math and is doing great.",
      ],
      name: "Teresa Schilling",
      location: "East Troy, WI",
      avatar: "/testimonials/us.jpg",
    },
  },
  homeschool: {
    uk: {
      quote: [
        "We are a home educating family who have used MathsOnline for several years.",
        "The programme provided our son with an excellent foundation for his iGCSE Maths examination (he has since gone on to study A-Level Maths at 6th Form).",
        "Overall, this programme has been a great asset to our home and I would wholeheartedly recommend it.",
      ],
      name: "Craig",
      location: "UK",
      avatar: "/testimonials/uk-homeschool.jpg",
    },
    us: {
      quote: [
        "As a homeschooling parent for the past 11 years, all three of our children love the program better than any other curriculum we have utilized.",
      ],
      name: "Rebekah Stearns",
      location: "Kuna, ID",
      avatar: "/testimonials/us-homeschool.jpg",
    },
  },
  awe: {
    uk: {
      quote: [
        "I love MathsOnline! As a Home Educating parent MathsOnline has given me the peace of mind, over many years, knowing that everything is covered and I haven’t missed anything. All of my children and my grandchildren have used MathsOnline at various stages and I see the progress they have made. The short bitesize lessons actually teach topics, rather than just test/revise like many maths programmes do, so it is perfect for Home Education.",
        "It is very easy to use and flexible so you can skip forward or go over things again if you need to. It is great for parents to see what lessons their child has completed and their progress with certificates you can print too. The parent area reports are suitable for sending to your Local Authority if you wish which is also a valuable resource. I know numerous Home Educators have been depending on MathsOnline for a great many years, so I totally recommend this resource.",
      ],
      name: "Debbie Towns",
      location: "AWE Home Education",
      avatar: "/testimonials/awe.jpg",
    },
  },
};

function TestimonialCard({
  market,
  variant,
}: {
  market: string;
  variant: OrderSidebarVariant;
}) {
  const testimonial = TESTIMONIALS[variant][market];

  if (!testimonial) {
    return null;
  }

  return (
    <Card>
      <CardContent className="flex gap-4">
        <div className="flex-1 space-y-4">
          {testimonial.quote.map((paragraph) => (
            <p key={paragraph}>{paragraph}</p>
          ))}

          <div>
            <strong>{testimonial.name}</strong>
            <br />
            <span className="text-muted-foreground">
              {testimonial.location}
            </span>
          </div>
        </div>

        <Image
          src={testimonial.avatar}
          alt={`${testimonial.name}'s photo`}
          width={96}
          height={96}
          className="size-24 shrink-0 rounded-full object-cover"
        />
      </CardContent>
    </Card>
  );
}

/**
 * The "Satisfaction Guaranteed" banner and the money-back modal behind it.
 *
 * Membership hand-rolls the modal out of a hidden div, two jQuery `animate`
 * calls and a click handler on its own backdrop; this is the shadcn `Dialog`,
 * which brings the focus trap, escape-to-close and `aria-modal` wiring that
 * version never had.
 *
 * Membership interpolates the brand name into this copy from `$brand->name`.
 * There is one brand now, so the name is a literal. The only thing that still
 * varies by market is whether there is a phone number to call.
 */
function GuaranteeDialog({ supportPhone }: { supportPhone: string | null }) {
  return (
    <Dialog>
      <DialogTrigger className="flex w-full items-center gap-4 rounded-xl bg-teal-100 p-6 text-left text-secondary-foreground md:p-8">
        <Image
          src="/guarantee/badge.png"
          alt=""
          width={80}
          height={80}
          className="w-20 shrink-0"
        />
        <strong>Satisfaction Guaranteed</strong>
      </DialogTrigger>

      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto text-center sm:max-w-lg">
        <DialogHeader className="items-center gap-2">
          <Image
            src="/guarantee/seal.jpg"
            alt=""
            width={150}
            height={150}
            className="mb-2 size-32"
          />
          <DialogTitle className="text-3xl font-bold">
            Money Back Guarantee
          </DialogTitle>
          <DialogDescription>
            My black and white 100% iron-clad satisfaction guarantee
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2">
          <h3 className="text-xl font-bold">You have nothing to risk.</h3>
          <p>
            Put MathsOnline&apos;s proven strategies to work for you and your
            children.
          </p>
          <p>
            If for any reason you are not completely satisfied with your
            program, just email{" "}
            {supportPhone ? "or call us " : "our friendly staff "}
            within 365 days and you&apos;ll receive a full 100% refund.
          </p>
        </div>

        <div className="space-y-2">
          <h3 className="text-xl font-bold">
            Your satisfaction is guaranteed.
          </h3>
          <p>
            That&apos;s a full year to put us to the test — all the risk is on
            us!
          </p>
          <p>
            No weasel clauses or hidden meanings here. If you&apos;re not happy
            then neither are we. My friendly staff will cheerfully return your
            money and we&apos;ll still be friends. That&apos;s it.
          </p>
        </div>
      </DialogContent>
    </Dialog>
  );
}

/** The two assurances at the foot of the column; Font Awesome glyphs become `lucide-react`. */
const ASSURANCES = [
  {
    icon: LockIcon,
    title: "Safe and Secure",
    description: "Your details are protected by SSL technology.",
  },
  {
    icon: ShieldHalfIcon,
    title: "Your Privacy",
    description: "We will never share your details.",
  },
];

function BuyWithConfidenceCard() {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base font-bold">
          Buy With Confidence
        </CardTitle>
      </CardHeader>

      <CardContent className="space-y-4">
        {ASSURANCES.map(({ icon: Icon, title, description }) => (
          <div key={title} className="flex items-center gap-4">
            <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-muted">
              <Icon className="size-5 text-primary" />
            </div>
            <div>
              <strong>{title}</strong>
              <br />
              <span className="text-muted-foreground">{description}</span>
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

export function OrderSidebar({
  market,
  variant = "default",
}: {
  market: Market;
  variant?: OrderSidebarVariant;
}) {
  return (
    <div className="space-y-4">
      {variant === "homeschool" ? <HomeschoolBanner /> : null}

      <TestimonialCard market={market.code} variant={variant} />

      {variant === "awe" ? (
        <Card>
          <CardContent>
            <Image
              src="/awe-discounts-logo.webp"
              alt="AWE Discounts"
              width={762}
              height={269}
              className="mx-auto h-auto w-full max-w-96"
            />
          </CardContent>
        </Card>
      ) : null}

      <GuaranteeDialog supportPhone={market.supportPhone} />
      <BuyWithConfidenceCard />
    </div>
  );
}
