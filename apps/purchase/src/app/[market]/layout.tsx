import Image from "next/image";

import { requireBrand } from "@/lib/brands/require-brand";

/**
 * Brand resolution for every purchase page: one deployment, one shared host, the market slug as the
 * first path segment, resolved by this plain dynamic segment.
 *
 * An unresolvable slug is a 404, not a redirect and not a GeoIP guess. The `notFound()` thrown here
 * escapes this segment's own boundary, so it renders the root `not-found.tsx` — correctly, since
 * with no brand there is no brand chrome to render a 404 inside.
 *
 * It also owns the chrome every flow shares, ported from membership's `layouts/app.blade.php` and
 * `orders/layouts/header.blade.php`: the brand-blue canvas, the centred logo, and the `max-w-6xl`
 * column the flows sit in — wide enough for the form-plus-sidebar split every membership order page
 * uses. The per-flow `<h1>` stays with the page, as membership's blades each render their own
 * ("Subscription Form", "Renew Your Membership", …).
 *
 * Membership keys the logo asset on `brands.id` (`resources/images/2/…`); here it is one asset for
 * every brand, and only its `alt` follows the brand's name. The v2 payload has no id to key on, and
 * the mark is the same whichever brand is served.
 */
export default async function BrandLayout({
  children,
  params,
}: LayoutProps<"/[market]">) {
  const { market: slug } = await params;

  const brand = await requireBrand(slug);

  return (
    <div className="flex flex-1 flex-col p-8">
      <header className="mx-auto mb-8 w-full max-w-6xl text-center">
        <Image
          src="/logo-white.png"
          alt={brand.name}
          width={300}
          height={72}
          className="mx-auto h-auto w-48"
          priority
        />
      </header>

      <main className="mx-auto w-full max-w-6xl space-y-4">{children}</main>
    </div>
  );
}
