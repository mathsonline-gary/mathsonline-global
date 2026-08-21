import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import { StepHeading } from "./step-heading";

describe("StepHeading", () => {
  it("is a heading, so the flow's steps show up in the document outline", () => {
    render(<StepHeading step={1}>Choose your membership</StepHeading>);

    expect(
      screen.getByRole("heading", { level: 2, name: /Choose your membership/ }),
    ).toBeInTheDocument();
  });

  it("draws the number the flow hard-codes, not a running count", () => {
    render(<StepHeading step={3}>Payment</StepHeading>);

    expect(screen.getByRole("heading", { level: 2 })).toHaveTextContent(
      "3Payment",
    );
  });
});
