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
import { getVanillaSrcDirs } from "./scripts/utility/vanillaSrcDirs";

const require = createRequire(import.meta.url);

const lodashEsPackageNames = () => {
    const lodashEsPath = path.resolve(VANILLA_ROOT, "node_modules/lodash-es");
    const jsFiles = fs.readdirSync(lodashEsPath);
    const results: string[] = [];
    for (const file of jsFiles) {
        if (file.endsWith(".js") && !file.startsWith("_")) {
            results.push(`lodash-es/${file.slice(0, -3)}`);
        }
    }
    return results;
};

const optimizedPackages = [
    "react",
    "lodash-es",
    ...lodashEsPackageNames(),
    "sprintf-js",
    "react-dom",
    "react-router-dom",
    "classnames",
    "warning",
    "prop-types",
    "@reach/popover > tabbable",
    "hoist-non-react-statics",
    "react-is",
    "typescript-fsa",
    "typescript-fsa-reducers",
    "react-router",
    "react-scrolllock",
    "stylis-rule-sheet",
    "react-input-autosize",
    "react-autosize-textarea/lib/TextareaAutosize",
    "react-transition-group",
    "raf",
    "react-day-picker/DayPicker",
    "react-relative-portal",
    "react-markdown",
    "slate",
    "use-sync-external-store/shim/index",
    "react-aria-live",
    "react/jsx-runtime",
    "scheduler",
    "p-debounce",
    "react-fast-compare",
    "@simonwep/pickr",
    "react-table",
    "keen-analysis",
    "emotion",
    "esbuild-wasm",
    "prettier/parser-typescript",
    "prettier/parser-postcss",
    "prettier/standalone",
    "react-swipeable",
    "@udecode/plate-autoformat",
    "@udecode/plate-basic-marks",
    "@udecode/plate-break",
    "@udecode/plate-combobox",
    "@udecode/plate-reset-node",
    "@udecode/plate-select",
    "@udecode/plate-serializer-csv",
    "@udecode/plate-serializer-docx",
    "@udecode/plate-serializer-md",
    "@udecode/plate-trailing-block",
    "@udecode/plate-floating",
    "@udecode/plate-mention",
    "@udecode/plate-horizontal-rule",
    "@udecode/plate-table",
    "prismjs/components/prism-php",
    "is-hotkey",
    "slate-react",
    "buffer",
    "@udecode/plate-highlight",
    "react-beautiful-dnd",
    "css-box-model",
    "@radix-ui/react-tabs",
    "diff",
    "dompurify",
    "jszip",
    "react-spring/renderprops.cjs",
    "@dnd-kit/core",
    "@dnd-kit/modifiers",
    "@dnd-kit/sortable",
    "@dnd-kit/utilities",
    "@tanstack/react-query-devtools",
    "@tanstack/react-query-devtools/build/lib/index.prod.js",
    "@reach/tabs",
    "@cfworker/json-schema",
    "moment",
    "@reach/tooltip",
    "@reach/portal",
    "react-select",
    "@reach/menu-button",
    "react-custom-scrollbars",
    "@udecode/plate-list",
    "@udecode/plate-block-quote",
    "@udecode/plate-code-block",
    "@udecode/plate-heading",
    "@udecode/plate-paragraph",
    "react-virtualized",
];

const warmupGlobs = [...getVanillaSrcDirs().map((dir) => dir + "/entries/*")];

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
            ],
            include: [
                "@vanilla/utils > tabbable",
                "@vanilla/ui > @reach/accordion",
                "@vanilla/ui > @reach/combobox",
                "@vanilla/ui > @reach/rect",
                ...optimizedPackages,
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
                {
                    find: "@library/headers/titleBarStyles",
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts/headers/TitleBar.classes.ts"),
                },
                {
                    find: "@library/headers/TitleBar.variables",
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts/headers/TitleBar.variables.ts"),
                },
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
                    find: /^@vanilla\/ui-library$/,
                    replacement: path.resolve(VANILLA_ROOT, "DONT_USE_VANILLA_UI_LIBRARY"),
                },
                {
                    find: /^@vanilla\/json-schema-forms$/,
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts/json-schema-forms"),
                },
                {
                    find: /^@vanilla\/react-utils$/,
                    // Point directly to the typescript src.
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-react-utils/src/index.ts"),
                },
                {
                    find: /^@vanilla\/dom-utils$/,
                    // Point directly to the typescript src.
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-dom-utils/src/index.ts"),
                },
                {
                    find: /^@vanilla\/utils$/,
                    // Point directly to the typescript src.
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-utils/index.ts"),
                },
                {
                    find: /^@vanilla\/icons$/,
                    // Point directly to the typescript src.
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-icons/index.ts"),
                },
                {
                    find: /^@vanilla\/i18n$/,
                    // Point directly to the typescript src.
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-i18n/src/index.ts"),
                },
                {
                    find: "@vanilla/json-schema-forms/src",
                    replacement: path.resolve(VANILLA_ROOT, "library/src/scripts/json-schema-forms"),
                },
                {
                    find: /^lodash\//,
                    replacement: path.join(VANILLA_ROOT, "node_modules/lodash-es/"),
                },
                {
                    find: /@vanilla\//,
                    replacement: path.resolve(VANILLA_ROOT, "packages/vanilla-"),
                },
                {
                    find: /^qs$/,
                    replacement: path.resolve(VANILLA_ROOT, "node_modules/qs-esm"),
                },
            ],
        },
    };
}

export function getAddonKeyFromChunkID(chunkID: string | undefined | null): string | null {
    if (!chunkID) {
        return null;
    }

    const addonName = /(addons\/themes|applications|plugins|themes)\/([^/]*)\/src\/scripts/.exec(chunkID)?.[2];
    return addonName ?? null;
}

export function isAddonEntry(chunkID: string | undefined | null): boolean {
    if (!chunkID) {
        return false;
    }

    return chunkID.includes("src/scripts/entries");
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
