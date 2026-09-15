/**
 * The 404 for the whole app, including an unknown market slug — there is no GeoIP
 * routing and no redirect-guessing.
 *
 * No slug resolved means no brand known, so this page carries no brand name, no
 * brand chrome and no links into a flow.
 */
export default function NotFound() {
  return (
    <main className="mx-auto flex w-full max-w-xl flex-1 flex-col justify-center gap-3 px-6 py-16">
      <p className="text-sm font-medium text-muted-foreground">404</p>
      <h1 className="text-2xl font-semibold tracking-tight">
        This page isn&apos;t available
      </h1>
      <p className="text-sm text-muted-foreground">
        Check the address, or head back to the site you came from to start
        again.
      </p>
    </main>
  );
}
