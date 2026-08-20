/**
 * Where the reCAPTCHA widget goes.
 *
 * A box, not a widget: the real one needs the market's site key and a submit
 * handler that reads its response token, and neither exists while these pages
 * are UI only.
 */
export function RecaptchaPlaceholder() {
  return (
    <div className="flex h-20 w-full max-w-xs items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground">
      reCAPTCHA
    </div>
  );
}
