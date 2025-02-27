import path from "path";
import { mergeConfig, UserConfig } from "vite";
import { VANILLA_ROOT } from "./scripts/env";
import reactPlugin from "@vitejs/plugin-react-swc";
import { getAddonKeyFromChunkID, isEntryChunk, isLibraryChunk, makeViteCommonConfig } from "./vite.commonConfig";
import fse from "fs-extra";

export function makeViteBuildConfig(entryHtmlFile: string): UserConfig {
    const buildConfig: UserConfig = {
        clearScreen: false,
        experimental: {
            renderBuiltUrl(filename: string, { hostType }: { hostType: "js" | "css" | "html" }) {
                return { relative: true };
            },
        },
        cacheDir: path.join(VANILLA_ROOT, "build/.cache"),
        esbuild: {
            legalComments: "external",
        },
        plugins: [
            reactPlugin({
                plugins: [
                    [
                        "@vanilla/plugin-emotion",
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
        ],
        build: {
            watch: null,
            chunkSizeWarningLimit: 1000,
            manifest: true,
            sourcemap: false,
            rollupOptions: {
                input: entryHtmlFile,
                onwarn: (warning, warn) => {
                    if (warning.message.includes("The comment will be removed to avoid issues")) {
                        return;
                    }
                    warn(warning);
                },
                output: {
                    compact: false,
                    minifyInternalExports: true,
                    generatedCode: "es2015",
                    chunkFileNames(chunkInfo) {
                        const addonName = getAddonKeyFromChunkID(chunkInfo.facadeModuleId);
                        if (addonName) {
                            if (isEntryChunk(chunkInfo.facadeModuleId)) {
                                return `entries/addons/${addonName}/[name].[hash].min.js`;
                            }

                            return `chunks/addons/${addonName}/[name].[hash].min.js`;
                        }

                        if (
                            chunkInfo.facadeModuleId?.includes("node_modules") ||
                            chunkInfo.moduleIds.map((id) => id.includes("node_modules")).reduce((a, b) => a && b, true)
                        ) {
                            return `vendor/[name].[hash].min.js`;
                        }

                        if (isLibraryChunk(chunkInfo.facadeModuleId)) {
                            return `chunks/library/[name].[hash].min.js`;
                        }
                        return `chunks/[name].[hash].min.js`;
                    },
                    manualChunks(id, { getModuleInfo }) {
                        const vendorPaths = [
                            "react",
                            "react-dom",
                            "react-router-dom",
                            "react-router",
                            "react-redux",
                            "redux",
                            "redux-thunk",
                            "react-loadable",
                            "@emotion",
                            "@tanstack/react-query",
                            "@tanstack/query-core",
                        ];
                        const isVendor = vendorPaths.some((vendorPath) => id.includes(`node_modules/${vendorPath}/`));
                        if (isVendor) {
                            return "vendor/react-core";
                        }
                    },
                },
            },
        },
        server: {
            host: "0.0.0.0",
            origin: "https://dev.vanilla.local",
            port: 3030,
            open: "https://dev.vanilla.local",
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
