/**
 * A numbered step heading inside a flow's form card — the blue circle and
 * label membership repeats in every `orders/create/*.blade.php`.
 *
 * The number is decoration, not state: each flow hard-codes its own sequence,
 * because the steps differ per flow (the new order chooses a plan first, the
 * renewal asks for an email first).
 */
export function StepHeading({
  step,
  children,
}: {
  step: number;
  children: React.ReactNode;
}) {
  return (
    <h2 className="mb-6 flex items-center text-xl font-bold">
      <span className="mr-2 flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground">
        {step}
      </span>
      {children}
    </h2>
  );
}
