import Script from "next/script";

/**
 * The brand's reCAPTCHA v2 checkbox.
 *
 * Google renders into any `.g-recaptcha` element it finds and injects a hidden
 * `<textarea name="g-recaptcha-response">` into the enclosing `<form>` — that textarea is the
 * token membership's order endpoints call `g_recaptcha_response`. Nothing here reads it: the forms
 * have no `onSubmit` yet, and `/api/v2` has no order path to post one to.
 *
 * Automatic rendering, so this stays a Server Component: no `"use client"`, no ref, no
 * `grecaptcha` global to type. It works because the div is server-rendered and `next/script`'s
 * default `afterInteractive` injects `api.js` after hydration, so the element is always in the DOM
 * before Google's bundle scans for it.
 *
 * ponytail: automatic rendering holds while each page has exactly one widget reached by a fresh
 * document load. Three things end that, and all three are fixed the same way — see below:
 *
 * 1. An internal navigation *into* a form page. The scan runs once, when the bundle loads, and
 *    nothing here navigates client-side today: no `Link`, no `useRouter`, no `redirect`.
 * 2. A second widget on one page. Google documents multiple widgets as an explicit-rendering
 *    feature, because each `render` call is what returns the id that addresses one of them.
 * 3. Needing `grecaptcha.reset()` — which the submit handler will, since a v2 token is single-use
 *    and a rejected submission has to clear it. No-arg `reset()` takes the first widget created,
 *    correct only while there is one.
 *
 * The fix for all three: `?render=explicit`, a `"use client"` leaf, and
 * `grecaptcha.ready(() => grecaptcha.render(el, { sitekey }))` from `next/script`'s `onReady`,
 * which fires on every mount — keeping the returned id. `api.js` is only a loader, so `ready` is
 * what waits for `render` to exist.
 */
export function Recaptcha({ siteKey }: { siteKey: string | null }) {
  // A brand with no site key configured has no widget. The API is what enforces the check.
  if (!siteKey) return null;

  return (
    <>
      <Script src="https://www.google.com/recaptcha/api.js" />
      <div className="g-recaptcha" data-sitekey={siteKey} />
    </>
  );
}
