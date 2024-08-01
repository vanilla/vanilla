/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { defineConfig } from "vite";
import { makeViteCommonConfig } from "./build/vite.commonConfig";
import path from "path";
import { VANILLA_ROOT } from "./build/scripts/env";
import { globbySync } from "globby";
import fs from "node:fs";

export default defineConfig(() => {
    const commonConfig = makeViteCommonConfig();

    const patternSuffix = "**/?(*.)+(spec).[tj]s?(x)";
    const globs = [
        ...globbySync(path.resolve(VANILLA_ROOT, "plugins/*"), { onlyDirectories: true }),
        ...globbySync(path.resolve(VANILLA_ROOT, "applications/*"), { onlyDirectories: true }),
    ]
        .map((dir) => fs.realpathSync(dir))
        .map((dir) => path.join(dir, "src/scripts"));
    const includes = [path.join("library/src/scripts"), path.join("packages"), ...globs].map((dir) =>
        path.join(dir, patternSuffix),
    );

    // Dev build
    return {
        ...commonConfig,
        mode: "development",
        define: {
            "process.env.NODE_ENV": '"test"',
        },
        test: {
            hookTimeout: 30000,
            globals: true,
            setupFiles: path.resolve(VANILLA_ROOT, "build/vitest.setup.ts"),
            globalSetup: path.resolve(VANILLA_ROOT, "build/vitest.globalSetup.ts"),
            environment: "jsdom",
            include: includes,
            exclude: [
                "**/node_modules/**",
                "**/dist/**",
                "**/cypress/**",
                "**/.{idea,git,cache,output,temp}/**",
                "**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build,eslint,prettier}.config.*",
                "RUN_STORYBOOK_TESTS" in process.env ? null : "**/StorybookTests*",
            ].filter((item) => !!item) as any,
            pool: "vmThreads",
            maxConcurrency: 20,
            poolOptions: {
                isolate: false,
                vmThreads: {
                    useAtomics: true,
                    minThreads: 2,
                    maxThreads: 10,
                },
            },
            testTimeout: 30000,
            coverage: {
                all: true,
                extension: ["ts", "tsx"],
                exclude: ["**/node_modules/**", "**/build/**", "**/dist/**", "**/coverage/**", "**/*.js"],
                reporter: ["json", "lcov"],
                reportsDirectory: "coverage/vitest",
            },
        },
    };
});
