import { notFound } from "next/navigation";

import { brandCodeForMarketSlug } from "./brand-code";
import { getBrand } from "./brands.api";
import type { Brand } from "./types";

/**
 * The brand for a `[market]` route, or a 404.
 *
 * The market slug is a plain dynamic segment — no middleware rewrite, no GeoIP — and names exactly
 * one brand. An unknown slug is a hard 404, not a redirect or a guess from the visitor's location.
 * The slug lookup runs first, so a segment that cannot name a brand never reaches the API.
 *
 * Kept apart from `brands.api` so that module stays a pure data boundary. Safe to call from a
 * layout and the pages beneath it: `getBrand` is request-cached.
 */
export async function requireBrand(segment: string): Promise<Brand> {
  const code = brandCodeForMarketSlug(segment);

  if (!code) {
    notFound();
  }

  const brand = await getBrand(code);

  if (!brand) {
    notFound();
  }

  return brand;
}
