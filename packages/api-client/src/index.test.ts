import { describe, expect, it } from "vitest";

import { ApiError, createApiClient, unwrap } from "./index";

const BASE_URL = "https://www.mathsonline.co/api/v2";

/**
 * A fetch that answers every call with the same response and keeps the requests, which is the only
 * way to see what the middleware did — openapi-fetch hands its `fetch` a built `Request`.
 */
function stubFetch(body: unknown, init?: ResponseInit) {
  const requests: Request[] = [];

  const fetch: typeof globalThis.fetch = async (input) => {
    requests.push(input as Request);

    return Response.json(body, init);
  };

  return { requests, fetch };
}

const MARKET = {
  code: "au",
  name: "Australia",
} as const;

describe("createApiClient", () => {
  it("resolves a path against the base URL and asks for JSON", async () => {
    const { requests, fetch } = stubFetch(MARKET);

    await createApiClient({ baseUrl: BASE_URL, fetch }).GET(
      "/markets/{marketCode}",
      { params: { path: { marketCode: "au" } } },
    );

    expect(requests[0]?.url).toBe(`${BASE_URL}/markets/au`);
    expect(requests[0]?.headers.get("Accept")).toBe("application/json");
  });

  it("resolves the token per request rather than capturing it once", async () => {
    const tokens = ["first", "second"];
    const { requests, fetch } = stubFetch(MARKET);
    const client = createApiClient({
      baseUrl: BASE_URL,
      fetch,
      token: () => tokens.shift(),
    });

    const get = () =>
      client.GET("/markets/{marketCode}", {
        params: { path: { marketCode: "au" } },
      });
    await get();
    await get();

    expect(requests.map((r) => r.headers.get("Authorization"))).toEqual([
      "Bearer first",
      "Bearer second",
    ]);
  });

  it("awaits an async token", async () => {
    const { requests, fetch } = stubFetch(MARKET);

    await createApiClient({
      baseUrl: BASE_URL,
      fetch,
      token: async () => "async-token",
    }).GET("/markets/{marketCode}", { params: { path: { marketCode: "au" } } });

    expect(requests[0]?.headers.get("Authorization")).toBe(
      "Bearer async-token",
    );
  });

  it("sends the request unauthenticated when the token resolves to undefined", async () => {
    const { requests, fetch } = stubFetch(MARKET);

    await createApiClient({
      baseUrl: BASE_URL,
      fetch,
      token: () => undefined,
    }).GET("/markets/{marketCode}", { params: { path: { marketCode: "au" } } });

    expect(requests[0]?.headers.get("Authorization")).toBeNull();
  });

  it("lets the token win over a header of the same name", async () => {
    const { requests, fetch } = stubFetch(MARKET);

    await createApiClient({
      baseUrl: BASE_URL,
      fetch,
      headers: { Authorization: "Bearer stale", "Accept-Language": "en-AU" },
      token: () => "fresh",
    }).GET("/markets/{marketCode}", { params: { path: { marketCode: "au" } } });

    expect(requests[0]?.headers.get("Authorization")).toBe("Bearer fresh");
    expect(requests[0]?.headers.get("Accept-Language")).toBe("en-AU");
  });

  it("is a factory — two clients share no state", () => {
    const { fetch } = stubFetch(MARKET);
    const config = { baseUrl: BASE_URL, fetch };

    expect(createApiClient(config)).not.toBe(createApiClient(config));
  });
});

describe("unwrap", () => {
  it("returns the payload of a successful result", () => {
    expect(
      unwrap({ data: MARKET, response: new Response(null, { status: 200 }) }),
    ).toBe(MARKET);
  });

  it("throws the normalised error when the result carries one", () => {
    expect(() =>
      unwrap({
        error: { message: "No market matches the code ZZ." },
        response: new Response(null, { status: 404 }),
      }),
    ).toThrowError(
      expect.objectContaining({
        name: "ApiError",
        status: 404,
        message: "No market matches the code ZZ.",
      }),
    );
  });

  it("throws when a 2xx carried no body", () => {
    try {
      unwrap({ response: new Response(null, { status: 204 }) });
      expect.unreachable("unwrap should have thrown");
    } catch (error) {
      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).status).toBe(204);
      expect((error as ApiError).message).toBe("HTTP 204 carried no body.");
    }
  });

  it("treats a null payload as data, not as absence", () => {
    expect(
      unwrap({ data: null, response: new Response(null, { status: 200 }) }),
    ).toBeNull();
  });
});
