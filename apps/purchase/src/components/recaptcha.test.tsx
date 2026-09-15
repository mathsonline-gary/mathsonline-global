import { render } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import { Recaptcha } from "./recaptcha";

describe("Recaptcha", () => {
  it("marks up the widget with the brand's site key for Google to find", () => {
    const { container } = render(<Recaptcha siteKey="site-key" />);

    // The class is the whole contract: it is what `api.js` scans for.
    const widget = container.querySelector(".g-recaptcha");
    expect(widget).toHaveAttribute("data-sitekey", "site-key");
  });

  it("renders nothing for a brand with no site key configured", () => {
    const { container } = render(<Recaptcha siteKey={null} />);

    expect(container).toBeEmptyDOMElement();
  });
});
