import Image from "next/image";

/**
 * The "half price for homeschoolers" artwork above the homeschool flows'
 * forms, ported from membership's `$isHomeschool` branches.
 *
 * The discount rate is baked into the image, which is why the route segment
 * that shows it is `homeschool-discount` rather than membership's `homeschool50`
 * — the rate can change, the flow can't.
 */
export function HomeschoolBanner() {
  return (
    <Image
      src="/homeschool-half-price.png"
      alt="Homeschoolers half price"
      width={830}
      height={217}
      className="mx-auto h-auto w-96"
      priority
    />
  );
}
