/**
 * Both entries share one pattern; commands under it run in sequence.
 */
export default {
  "*": [
    // Format and re-stage. `--ignore-unknown` skips files prettier has no parser for.
    "prettier --write --ignore-unknown",

    // Gate. Ignores the staged file list; always runs whole-graph.
    () => "pnpm turbo run lint check-types test",
  ],
};
