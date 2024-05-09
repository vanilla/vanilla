/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import path from "path";
import type { UserConfig } from "vite";
import { VANILLA_ROOT } from "./scripts/env";
import EntryModel from "./scripts/utility/EntryModel";
import fs from "fs";
import { createRequire } from "node:module";

const require = createRequire(import.meta.url);
export const warmupGlobs = [
    "**/src/scripts/**/*.ts",
    "**/src/scripts/**/*.tsx",
    "build/.vite/*.html",
    "build/.vite/*.html",
    "lodash-es/*",
    "lodash-es",
    "lodash",
    "node_modules/@reach/*",
    "react-swipeable",
    "react-table",
    "formik",
    "lodash-es/flatten",
    "formik",
    "lodash-es/clamp",
    "@reach/menu-button",
    "lodash-es/isEqual",
    "lodash-es/range",
    "lodash-es/startCase",
    "lodash-es/sortBy",
];

export function makeViteCommonConfig(): UserConfig {
    const entryModel = new EntryModel();

    return {
        root: VANILLA_ROOT,
        plugins: [fixReactVirtualizedPlugin()],
        optimizeDeps: {
            include: warmupGlobs,
            exclude: ["plugins/rich-editor/src/scripts/@types/quill.d.ts", "node_modules/.cache", "build/.cache"],
            holdUntilCrawlEnd: false,
        },
        define: {
            "process.env.IS_WEBPACK": true,
        },
        server: {
            warmup: {
                clientFiles: warmupGlobs,
            },
            watch: {
                ignored: [
                    "**/cache/**",
                    "**/tests/**",
                    "**/vendor/**",
                    "**/docker/**",
                    "**/node_modules/**",
                    "**/.idea/**",
                    "**/.git/**",
                    "**/views/**",
                    "**/conf/**",
                ],
            },
        },
        css: {
            modules: false,
            postcss: {
                map: true,
                plugins: [],
            },
        },
        resolve: {
            // Required so that vite can resolve node_modules of symlinked plugins into the top-level `node_modules`.
            preserveSymlinks: true,
            extensions: [".mjs", ".js", ".jsx", ".ts", ".tsx", ".json", ".scss"],
            alias: [
                ...entryModel.aliases,
                {
                    find: "@dashboard/compatibilityStyles/Leaderboard.variables",
                    replacement: path.resolve(
                        VANILLA_ROOT,
                        "library/src/scripts/leaderboardWidget/LeaderboardWidget.variables.ts",
                    ),
                },
                {
                    find: "@dashboard/compatibilityStyles/Leaderboard.styles",
                    replacement: path.resolve(
                        VANILLA_ROOT,
                        "library/src/scripts/leaderboardWidget/LeaderboardWidget.styles.ts",
                    ),
                },
                {
                    find: /~library-scss/,
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scss"),
                },
                {
                    find: /^~/,
                    replacement: path.resolve(VANILLA_ROOT, "node_modules") + "/",
                },
                {
                    find: /^react-select$/,
                    replacement: require.resolve("react-select/dist/react-select.esm.js"),
                },
                {
                    find: /^typestyle$/,
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts/styles/styleShim.ts"),
                },
                // Legacy mapping that doesn't exist any more. Even has a lint rule against it.
                {
                    find: "@vanilla/library/src/scripts",
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts"),
                },
                {
                    find: "lodash",
                    replacement: "lodash-es",
                },
            ],
        },
    };
}

export function getAddonKeyFromChunkID(chunkID: string | undefined | null): string | null {
    if (!chunkID) {
        return null;
    }

    const addonName = /(applications|plugins|themes)\/([^\/]*)\/src\/scripts/.exec(chunkID)?.[2];
    return addonName ?? null;
}

export function isEntryChunk(chunkID: string | undefined | null): boolean {
    if (!chunkID) {
        return false;
    }

    return chunkID.includes("src/scripts/entries");
}

export function isLibraryChunk(chunkID: string | undefined | null): boolean {
    if (!chunkID) {
        return false;
    }

    return chunkID.includes("/packages/vanilla") || chunkID.includes("/library/src");
}

function fixReactVirtualizedPlugin() {
    const WRONG_CODE = `import { bpfrpt_proptype_WindowScroller } from "../WindowScroller.js";`;

    return {
        name: "my:react-virtualized",
        configResolved() {
            const file = require
                .resolve("react-virtualized")
                .replace(
                    path.join("dist", "commonjs", "index.js"),
                    path.join("dist", "es", "WindowScroller", "utils", "onScroll.js"),
                );
            const code = fs.readFileSync(file, "utf-8");
            const modified = code.replace(WRONG_CODE, "");
            fs.writeFileSync(file, modified);
        },
    };
}
