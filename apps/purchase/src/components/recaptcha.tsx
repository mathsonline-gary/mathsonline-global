import Script from "next/script";

/**
 * The brand's reCAPTCHA Enterprise checkbox.
 *
 * Google renders into the `.g-recaptcha` element and leaves the token in a hidden
 * `<textarea name="g-recaptcha-response">` inside the enclosing `<form>`. Nothing reads it yet.
 */
export function Recaptcha({ siteKey }: { siteKey: string | null }) {
  if (!siteKey) return null;

  return (
    <>
      <Script src="https://www.google.com/recaptcha/enterprise.js" />
      <div className="g-recaptcha" data-sitekey={siteKey} />
    </>
  );
}
