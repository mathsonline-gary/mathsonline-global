import { describe, expect, it } from "vitest";

import { ApiError } from "./errors";

/** A response carrying only what {@link ApiError.from} reads off it. */
function responseWith(
  status: number,
  statusText = "",
  headers: Record<string, string> = {},
): Response {
  return new Response(null, { status, statusText, headers });
}

describe("ApiError.from", () => {
  it("takes its message from the envelope the description guarantees", () => {
    const error = ApiError.from(responseWith(404, "Not Found"), {
      message: "No market matches the code AU.",
    });

    expect(error.message).toBe("No market matches the code AU.");
    expect(error.status).toBe(404);
    expect(error.isNotFound).toBe(true);
  });

  it("falls back to the status line when a gateway answers with HTML", () => {
    const error = ApiError.from(responseWith(502, "Bad Gateway"), "<html>");

    expect(error.message).toBe("HTTP 502 Bad Gateway");
    expect(error.isServerError).toBe(true);
  });

  it("falls back without a trailing space when there is no status text", () => {
    expect(ApiError.from(responseWith(500), {}).message).toBe("HTTP 500");
  });

  it("collects the per-field messages of a 422", () => {
    const error = ApiError.from(responseWith(422, "Unprocessable Content"), {
      message: "The given data was invalid.",
      errors: {
        email: ["The email field is required."],
        "children.0.name": ["The name must be a string.", "Too short."],
      },
    });

    expect(error.isValidationError).toBe(true);
    expect(error.validationErrors).toEqual({
      email: ["The email field is required."],
      "children.0.name": ["The name must be a string.", "Too short."],
    });
  });

  it("drops fields whose messages are not an array of strings", () => {
    const error = ApiError.from(responseWith(422), {
      errors: { email: ["Required."], token: "Required.", plan: [1] },
    });

    expect(error.validationErrors).toEqual({ email: ["Required."] });
  });

  it("leaves validationErrors unset when no field survives", () => {
    const error = ApiError.from(responseWith(422), { errors: {} });

    expect(error.validationErrors).toBeUndefined();
  });

  it("reads Retry-After off a 429", () => {
    const error = ApiError.from(
      responseWith(429, "Too Many Requests", { "Retry-After": "60" }),
      { message: "Too Many Attempts." },
    );

    expect(error.isRateLimited).toBe(true);
    expect(error.retryAfterSeconds).toBe(60);
  });

  it("ignores a Retry-After that is absent or not a positive number", () => {
    expect(
      ApiError.from(responseWith(429), {}).retryAfterSeconds,
    ).toBeUndefined();
    expect(
      ApiError.from(
        responseWith(429, "", {
          "Retry-After": "Wed, 21 Oct 2026 07:28:00 GMT",
        }),
        {},
      ).retryAfterSeconds,
    ).toBeUndefined();
  });

  it("distinguishes 401 from 403", () => {
    expect(ApiError.from(responseWith(401), {}).isUnauthenticated).toBe(true);
    expect(ApiError.from(responseWith(403), {}).isUnauthenticated).toBe(false);
  });

  it("stays an Error, so a call site can catch and rethrow it as one", () => {
    const error = ApiError.from(responseWith(500), { message: "Server Error" });

    expect(error).toBeInstanceOf(Error);
    expect(error.name).toBe("ApiError");
    expect(error.body).toEqual({ message: "Server Error" });
  });
});
