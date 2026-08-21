// `toBeInTheDocument`, `toHaveAccessibleName` and the rest, registered on vitest's `expect`.
import "@testing-library/jest-dom/vitest";

import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";

// jsdom keeps one document for the whole file, so a render left behind would be found by the next
// test's query. Testing Library only auto-cleans when the global test hooks are installed, which
// they are not here — `describe`/`it` are imported per file rather than declared global.
afterEach(cleanup);
