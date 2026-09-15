/**
 * A search param's raw value is `string | string[] | undefined` — an array when the key
 * repeated in the URL. Every reader here wants the single-value case only, so a repeated
 * key is treated the same as an absent one rather than guessing which occurrence wins.
 */
export function readSearchParam(
  value: string | string[] | undefined,
): string | undefined {
  return typeof value === "string" ? value : undefined;
}
