import { describe, expect, it } from "vitest";

import { isMarketCode } from "./market-code";

describe("isMarketCode", () => {
  it("accepts every market the description enumerates", () => {
    for (const code of ["au", "us", "uk"]) {
      expect(isMarketCode(code)).toBe(true);
    }
  });

  it("rejects a country this application does not serve", () => {
    expect(isMarketCode("de")).toBe(false);
  });

  it("matches exactly — an upper-case segment is a miss, not a redirect", () => {
    expect(isMarketCode("AU")).toBe(false);
  });

  it("rejects the crawler noise that shares the segment's position", () => {
    for (const segment of ["favicon.ico", "robots.txt", "", "/au"]) {
      expect(isMarketCode(segment)).toBe(false);
    }
  });

  it("rejects an inherited property name", () => {
    // The reason the implementation uses `Object.hasOwn` rather than `in`.
    for (const key of ["toString", "constructor", "__proto__"]) {
      expect(isMarketCode(key)).toBe(false);
    }
  });
});
