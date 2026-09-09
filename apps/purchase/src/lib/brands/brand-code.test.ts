import { describe, expect, it } from "vitest";

import { brandCodeForMarketSlug } from "./brand-code";

describe("brandCodeForMarketSlug", () => {
  it("maps every market slug this application serves onto its brand", () => {
    expect(brandCodeForMarketSlug("au")).toBe("MOL_AU");
    expect(brandCodeForMarketSlug("uk")).toBe("MOL_UK");
    expect(brandCodeForMarketSlug("us")).toBe("MOL_US");
  });

  it("rejects a country this application does not serve", () => {
    expect(brandCodeForMarketSlug("de")).toBeUndefined();
  });

  it("matches exactly — an upper-case segment is a miss, not a redirect", () => {
    expect(brandCodeForMarketSlug("AU")).toBeUndefined();
  });

  it("rejects a brand code — the URL carries a slug, not an identity", () => {
    expect(brandCodeForMarketSlug("MOL_AU")).toBeUndefined();
  });

  it("rejects the crawler noise that shares the segment's position", () => {
    for (const segment of ["favicon.ico", "robots.txt", "", "/au"]) {
      expect(brandCodeForMarketSlug(segment)).toBeUndefined();
    }
  });

  it("rejects an inherited property name", () => {
    // The reason the implementation uses `Object.hasOwn` rather than `in`.
    for (const key of ["toString", "constructor", "__proto__"]) {
      expect(brandCodeForMarketSlug(key)).toBeUndefined();
    }
  });
});
