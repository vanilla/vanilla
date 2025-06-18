import path from "path";
import { mergeConfig, UserConfig } from "vite";
import { VANILLA_ROOT } from "./scripts/env";
import reactPlugin from "@vitejs/plugin-react-swc";
import { getAddonKeyFromChunkID, isAddonEntry, makeViteCommonConfig } from "./vite.commonConfig";
import { VanillaManifestPlugin } from "./VanillaManifestPlugin";

export function makeViteBuildConfig(buildSection: string, entryHtmlFile: string): UserConfig {
    const buildConfig: UserConfig = {
        clearScreen: false,
        experimental: {
            renderBuiltUrl(filename: string, { hostType }: { hostType: "js" | "css" | "html" }) {
                return { relative: true };
            },
        },
        cacheDir: path.join(VANILLA_ROOT, "node_modules/.vite"),
        esbuild: {
            legalComments: "external",
        },
        resolve: {
            alias: [
                {
                    find: "@storybook/test",
                    replacement: path.resolve(VANILLA_ROOT, "dont-import-storybook-outside-of-storybook"),
                },
                {
                    find: "@testing-library.*",
                    replacement: path.resolve(VANILLA_ROOT, "dont-import-testinglibrary-outside-of-tests"),
                },
            ],
        },
        plugins: [
            reactPlugin({
                plugins: [
                    [
                        "@swc/plugin-emotion",
                        {
                            // default is true. It will be disabled when build type is production.
                            sourceMap: false,
                            // default is 'dev-only'.
                            autoLabel: "always",
                            // default is '[local]'.
                            // Allowed values: `[local]` `[filename]` and `[dirname]`
                            // This option only works when autoLabel is set to 'dev-only' or 'always'.
                            // It allows you to define the format of the resulting label.
                            // The format is defined via string where variable parts are enclosed in square brackets [].
                            // For example labelFormat: "my-classname--[local]", where [local] will be replaced with the name of the variable the result is assigned to.
                            labelFormat: "[filename]-[local]",
                        },
                    ],
                ],
            }),
            VanillaManifestPlugin(buildSection),
        ],
        build: {
            watch: null,
            chunkSizeWarningLimit: 1000,
            // We have our own manifest plugin.
            manifest: false,
            sourcemap: false,
            modulePreload: false,
            rollupOptions: {
                input: entryHtmlFile,
                onwarn: (warning, warn) => {
                    if (warning.message.includes("The comment will be removed to avoid issues")) {
                        return;
                    }
                    warn(warning);
                },
                output: {
                    inlineDynamicImports: false,
                    compact: true,
                    minifyInternalExports: true,
                    generatedCode: "es2015",
                    entryFileNames(chunkInfo) {
                        return "entries/[name].[hash].min.js";
                    },
                    assetFileNames(chunkInfo) {
                        if (chunkInfo.names.some((name) => name.endsWith(".css"))) {
                            const addonName =
                                chunkInfo.originalFileNames
                                    ?.map((name) => getAddonKeyFromChunkID(name))
                                    ?.filter((val) => val)?.[0] ?? null;

                            if (addonName) {
                                return `chunks/addons/${addonName}/[name].[hash].css`;
                            }
                        }
                        return `assets/[name].[hash][extname]`;
                    },
                    chunkFileNames(chunkInfo) {
                        const { moduleIds } = chunkInfo;
                        let addonName;
                        for (const moduleId of moduleIds) {
                            const potentialAddonName = getAddonKeyFromChunkID(moduleId);
                            if (potentialAddonName && isAddonEntry(moduleId)) {
                                return `entries/addons/${potentialAddonName}/[name].[hash].min.js`;
                            } else if (potentialAddonName) {
                                addonName = potentialAddonName;
                            }
                        }

                        if (addonName) {
                            return `chunks/addons/${addonName}/[name].[hash].min.js`;
                        }

                        if (chunkInfo.moduleIds.includes(entryHtmlFile)) {
                            return `entries/${buildSection}.[hash].min.js`;
                        }

                        if (
                            chunkInfo.facadeModuleId?.includes("node_modules") ||
                            chunkInfo.moduleIds.map((id) => id.includes("node_modules")).reduce((a, b) => a && b, true)
                        ) {
                            return `vendor/[name].[hash].min.js`;
                        }

                        return `chunks/[name].[hash].min.js`;
                    },
                    manualChunks(id, { getModuleInfo }) {
                        const vendorPaths = [
                            "react",
                            "react-dom",
                            "react-router-dom",
                            "react-router",
                            "react-loadable",
                            "@emotion",
                            "@tanstack/react-query",
                            "@tanstack/query-core",
                            "axios",
                        ];
                        const isVendor = vendorPaths.some((vendorPath) => id.includes(`node_modules/${vendorPath}/`));
                        if (isVendor) {
                            return "vendor/react-core";
                        }

                        const reduxPaths = ["react-redux", "redux", "redux-thunk", "@reduxjs/toolkit", "immer"];
                        const isRedux = reduxPaths.some((reduxPath) => id.includes(`node_modules/${reduxPath}/`));
                        if (isRedux) {
                            return "vendor/redux";
                        }

                        const isLodash = id.includes("node_modules/lodash");
                        if (isLodash) {
                            return "vendor/lodash";
                        }

                        const platePaths = ["@udecode/plate-core", "slate"];
                        const isPlate = platePaths.some((platePath) => id.includes(`node_modules/${platePath}`));

                        if (isPlate) {
                            return "vendor/react-plate";
                        }

                        if (id.includes("node_modules/moment/")) {
                            return "vendor/moment";
                        }

                        if (id.includes("node_modules/react-select/")) {
                            return "vendor/react-select";
                        }

                        if (id.includes("node_modules/react-spring/")) {
                            return "vendor/react-spring";
                        }

                        const markdownPaths = ["micromark", "unified", "remark-parse", "micromark", "vfile"];
                        const isMarkdown = markdownPaths.some((markdownPath) =>
                            id.includes(`node_modules/${markdownPath}`),
                        );
                        if (isMarkdown) {
                            return "vendor/markdown";
                        }
                    },
                    dynamicImportInCjs: true,
                    esModule: true,
                    hoistTransitiveImports: false,
                },
            },
        },
        server: {
            host: "0.0.0.0",
            port: 3030,
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
                "Access-Control-Allow-Methods": "POST, GET, PUT, DELETE, OPTIONS",
            },
            hmr: {
                host: "127.0.0.1",
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
    };

    return mergeConfig(makeViteCommonConfig(), buildConfig);
}
