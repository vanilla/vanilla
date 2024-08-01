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

const warmupGlobs = [
    "lodash-es",
    "react",
    "react-dom",
    "sprintf-js",
    "immer",
    "typescript-fsa-reducers",
    "react-redux",
    "redux",
    "react-router",
    "axios",
    "prismjs",
    "react-virtualized",
    "slate-react",
    "slate",
    "formik",
    "moment",
    "react-autosize-textarea",
    "react-day-picker",
    "is-hotkey",
    "qs",
    "react-aria-live",
    "classnames",
    "csx",
    "@emotion/css",
    "react-spring",
    "@reach/skip-nav",
    "react-router-dom",
    "history",
    "@reduxjs/toolkit",
    "p-debounce",
    "lodash-es/omit",
    "lodash-es/debounce",
    "lodash-es/random",
    "lodash-es/throttle",
    "redux-thunk",
    "@tanstack/react-query",
    "@tanstack/react-query-devtools",
    "@tanstack/react-query-devtools/build/lib/index.prod.js",
    "react-scrolllock",
    "lodash-es/clone",
    "lodash-es/difference",
    "typescript-fsa",
    "lodash-es/memoize",
    "lodash-es/isEmpty",
    "lodash-es/merge",
    "react-select",
    "lodash-es/set",
    "lodash-es/unset",
    "lodash-es/get",
    "lodash-es/intersection",
    "@reach/tooltip",
    "@reach/portal",
    "lodash-es/cloneDeep",
    "@reach/popover",
    "lodash-es/keyBy",
    "lodash-es/isEqual",
    "lodash/isEqual",
    "@monaco-editor/react",
    "@reach/tabs",
    "lodash-es/sortBy",
    "lodash-es/pick",
    "react-table",
    "react-custom-scrollbars",
    "react-day-picker/DayPicker",
    "react-relative-portal",
    "react-autosize-textarea/lib/TextareaAutosize",
    "@simonwep/pickr",
    "react-beautiful-dnd",
    "css-box-model",
    "lodash-es/clamp",
    "@reach/menu-button",
    "lodash-es/range",
    "react-swipeable",
    "lodash-es/startCase",
    "lodash-es/flatten",
    "react-grid-layout",
    "@nivo/line",
    "@nivo/pie",
    "@nivo/bar",
    "lodash-es/uniq",
    "lodash-es/uniqBy",
    "lodash-es/capitalize",
    "lodash-es/lowerCase",
    "lodash-es/chunk",
    "lodash-es/isUndefined",
    "keen-analysis",
    "@reach/menu-button",
    "lodash-es/range",
    "lodash-es/clamp",
    "@udecode/plate-common",
    "react-swipeable",
    "lodash-es/startCase",
    "lodash/snakeCase",
    "lodash/startCase",
    "swagger-ui",
    "immutable",
];

export function makeViteCommonConfig(): UserConfig {
    const entryModel = new EntryModel();

    return {
        root: VANILLA_ROOT,
        plugins: [fixReactVirtualizedPlugin()],
        optimizeDeps: {
            exclude: [
                "@vanilla/utils",
                "@vanilla/ui",
                "@vanilla/react-utils",
                "@vanilla/dom-utils",
                "@vanilla/icons",
                "@vanilla/i18n",
                "@vanilla/json-schema-forms",
                `fake-dep-${Math.random()}`,
            ],
            include: [
                "@vanilla/utils > tabbable",
                "@vanilla/ui > @reach/accordion",
                "@vanilla/ui > @reach/combobox",
                "@vanilla/ui > @reach/rect",
                ...warmupGlobs,
            ],
            holdUntilCrawlEnd: true,
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
                    find: /$lodash^/,
                    replacement: "lodash-es",
                },
                {
                    find: /@vanilla\//,
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-"),
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
