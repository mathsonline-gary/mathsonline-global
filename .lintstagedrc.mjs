/**
 * Both entries share one pattern on purpose: commands listed under a single pattern run in
 * sequence, and these two must not overlap — the second reads the files the first rewrites.
 * Separate patterns would run concurrently.
 */
export default {
  "*": [
    // Formats and re-stages. `--ignore-unknown` is what makes the bare `*` pattern safe: anything
    // prettier has no parser for is skipped rather than failing the commit. `.prettierignore`
    // still applies, so the vendored skills stay untouched.
    "prettier --write --ignore-unknown",

    // The gate. Deliberately ignores the file list it is handed:
    //
    //   - `redocly lint` takes the description's entrypoint, not individual files, so the YAML
    //     under packages/openapi-v2/ cannot be checked per-path at all.
    //   - Type errors are cross-file. Checking only the staged paths would miss the case this
    //     repository exists to catch — a schema change that breaks a front end that was not
    //     touched.
    //   - The same goes for `test`: a test lives beside the module it covers, but what breaks it
    //     is usually a change somewhere else. Running only the tests next to the staged files
    //     would check the one package least likely to be at fault.
    //
    // Cheap despite being whole-graph, because turbo only re-runs the packages the change
    // actually affects and restores the rest from cache. It also runs inside lint-staged's stash,
    // so it sees exactly the tree being committed rather than the working tree.
    () => "pnpm turbo run lint check-types test",
  ],
};
