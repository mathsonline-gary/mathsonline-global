import type { components } from "../../dist/types";

type ErrorBody = components["schemas"]["Error"];
type ValidationErrorBody = components["schemas"]["ValidationError"];

/**
 * Every non-2xx response, normalised. The description guarantees a `{message}` envelope for any
 * error Laravel renders on a JSON request, so `message` is always populated — but a proxy or a
 * gateway can still answer with HTML, hence the fallbacks.
 */
export class ApiError extends Error {
  readonly status: number;
  /** Field name (dot notation for nested and array fields) to its failure messages. 422 only. */
  readonly validationErrors?: Record<string, string[]>;
  /** `Retry-After` in seconds, when the rate limiter set it. 429 only. */
  readonly retryAfterSeconds?: number;
  readonly body: unknown;

  constructor(
    message: string,
    init: {
      status: number;
      body?: unknown;
      validationErrors?: Record<string, string[]>;
      retryAfterSeconds?: number;
    },
  ) {
    super(message);
    this.name = "ApiError";
    this.status = init.status;
    this.body = init.body;
    this.validationErrors = init.validationErrors;
    this.retryAfterSeconds = init.retryAfterSeconds;
  }

  static from(response: Response, body: unknown): ApiError {
    const retryAfter = Number(response.headers.get("Retry-After"));

    return new ApiError(messageOf(body) ?? `HTTP ${response.status} ${response.statusText}`.trim(), {
      status: response.status,
      body,
      validationErrors: validationErrorsOf(body),
      retryAfterSeconds: Number.isFinite(retryAfter) && retryAfter > 0 ? retryAfter : undefined,
    });
  }

  /** 422 — the request was well-formed but the payload failed validation. */
  get isValidationError(): boolean {
    return this.status === 422;
  }

  /** 401 — no or expired credentials. Distinct from 403, which means authenticated but refused. */
  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  get isNotFound(): boolean {
    return this.status === 404;
  }

  get isRateLimited(): boolean {
    return this.status === 429;
  }

  /** 5xx — retrying may succeed; nothing about the request needs to change. */
  get isServerError(): boolean {
    return this.status >= 500;
  }
}

function messageOf(body: unknown): string | undefined {
  if (!isRecord(body)) return undefined;
  const { message } = body as Partial<ErrorBody>;

  return typeof message === "string" && message.length > 0 ? message : undefined;
}

function validationErrorsOf(body: unknown): Record<string, string[]> | undefined {
  if (!isRecord(body)) return undefined;
  const { errors } = body as Partial<ValidationErrorBody>;
  if (!isRecord(errors)) return undefined;

  const normalised: Record<string, string[]> = {};
  for (const [field, messages] of Object.entries(errors)) {
    if (Array.isArray(messages) && messages.every((m) => typeof m === "string")) {
      normalised[field] = messages;
    }
  }

  return Object.keys(normalised).length > 0 ? normalised : undefined;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
