/**
 * The 404 for the whole app, including an unknown market slug — issue #4 rules
 * out GeoIP routing and redirect-guessing, so an unrecognised first path
 * segment lands here.
 *
 * No market resolved means no market known, so this page carries no brand name,
 * no market chrome and no links into a flow. Copy is purchase-web's own
 * (issue #3: membership returns facts, purchase-web owns customer-facing copy).
 */
export default function NotFound() {
  return (
    <main className="mx-auto flex w-full max-w-xl flex-1 flex-col justify-center gap-3 px-6 py-16">
      <p className="text-muted-foreground text-sm font-medium">404</p>
      <h1 className="text-2xl font-semibold tracking-tight">
        This page isn&apos;t available
      </h1>
      <p className="text-muted-foreground text-sm">
        Check the address, or head back to the site you came from to start
        again.
      </p>
    </main>
  );
}
