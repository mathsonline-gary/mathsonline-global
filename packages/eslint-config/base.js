import { defineConfig, globalIgnores } from "eslint/config";
import js from "@eslint/js";
import tseslint from "typescript-eslint";
import prettier from "eslint-config-prettier";

export const baseConfig = defineConfig([
  js.configs.recommended,
  ...tseslint.configs.recommended,
  prettier,
  globalIgnores(["dist/**", "node_modules/**"]),
]);

export default baseConfig;
