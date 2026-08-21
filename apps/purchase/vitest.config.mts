import react from "@vitejs/plugin-react";
import { defineConfig } from "vitest/config";

/**
 * Vitest rather than `next/jest`: the app is the only thing Next builds here, and a test run that
 * goes through the Next compiler pays for a bundler it never uses. What is actually needed from
 * that pipeline is JSX and the `@/*` alias — the plugin below and `resolve.tsconfigPaths`, which
 * reads the alias from `tsconfig.json` so the two cannot drift.
 *
 * The React Compiler is deliberately absent. It is a build-time optimisation, and a component
 * whose behaviour changes under it is a bug this suite should see rather than hide.
 */
export default defineConfig({
  plugins: [react()],
  resolve: { tsconfigPaths: true },
  test: {
    environment: "jsdom",
    setupFiles: ["./vitest.setup.ts"],
    include: ["src/**/*.test.{ts,tsx}"],
  },
});
