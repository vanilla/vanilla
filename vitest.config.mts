/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { defineConfig, UserConfig, mergeConfig } from "vite";
import { ProjectConfig } from "vitest/node";
import { makeViteCommonConfig } from "./build/vite.commonConfig";
import path from "path";
import { VANILLA_ROOT } from "./build/scripts/env";
import { globbySync } from "globby";
import fs from "node:fs";
import fse from "fs-extra";

export default defineConfig(() => {
    const coverageDir = path.resolve(VANILLA_ROOT, "coverage/vitest");
    fse.ensureDirSync(coverageDir);
    const commonConfig = makeViteCommonConfig();

    const patternSuffix = "**/?(*.)+(spec).[tj]s?(x)";
    const globs = [
        ...globbySync(path.resolve(VANILLA_ROOT, "plugins/*"), { onlyDirectories: true }),
        ...globbySync(path.resolve(VANILLA_ROOT, "applications/*"), { onlyDirectories: true }),
    ]
        .map((dir) => fs.realpathSync(dir))
        .map((dir) => path.join(dir, "src/scripts"))
        .filter((dir) => fs.existsSync(dir));
    const includes = [path.join("library/src/scripts"), path.join("packages"), ...globs].map((dir) =>
        path.join(dir, patternSuffix),
    );
    const ownConfig: UserConfig = {
        mode: "development",
        define: {
            "process.env.NODE_ENV": '"test"',
        },
        test: {
            retry: 2,
            sequence: {
                setupFiles: "list",
            },
            onConsoleLog(log: string, type: "stdout" | "stderr"): boolean | void {
                if (process.env.SILENCE_CONSOLE) {
                    return false;
                }

                if (log.includes("boom is not a function")) {
                    // Intended error.
                    return false;
                }
            },
            server: {
                deps: {
                    inline: [/@radix-ui/], // necessary to fix this issue for React 17 https://github.com/radix-ui/primitives/issues/2974
                },
            },
            hookTimeout: 30000,
            globals: true,
            setupFiles: path.resolve(VANILLA_ROOT, "build/vitest.setup.ts"),
            globalSetup: path.resolve(VANILLA_ROOT, "build/vitest.globalSetup.ts"),
            environment: "jsdom",
            environmentOptions: {
                jsdom: {
                    url: "http://localhost",
                },
            },
            include: includes,
            exclude: [
                "**/node_modules/**",
                "**/dist/**",
                "**/cypress/**",
                "**/.{idea,git,cache,output,temp}/**",
                "**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build,eslint,prettier}.config.*",
                "RUN_STORYBOOK_TESTS" in process.env ? null : "**/StorybookTests*",
            ].filter((item) => !!item) as any,
            pool: "threads",
            maxConcurrency: 1,
            isolate: true,
            poolOptions: {
                forks: {
                    minForks: 2,
                    maxForks: 10,
                },
                threads: {
                    minThreads: 2,
                    maxThreads: 10,
                },
            },
            testTimeout: 60000,
            coverage: {
                all: true,
                extension: ["ts", "tsx"],
                // include: includes.map((include) => path.join(include, "**.ts*")),
                exclude: [
                    "coverage/**",
                    "dist/**",
                    "**/[.]**",
                    "packages/*/test?(s)/**",
                    "**/*.d.ts",
                    "**/virtual:*",
                    "**/__x00__*",
                    "**/\x00*",
                    "cypress/**",
                    "test?(s)/**",
                    "test?(-*).?(c|m)[jt]s?(x)",
                    "**/*{.,-}{test,spec}?(-d).?(c|m)[jt]s?(x)",
                    "**/__tests__/**",
                    "**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*",
                    "**/vitest.{workspace,projects}.[jt]s?(on)",
                    "**/.{eslint,mocha,prettier}rc.{?(c|m)js,yml}",
                    "**/node_modules/**",
                    "**/build/**",
                    "**/dist/**",
                    "**/coverage/**",

                    // no idea why these are even showing up, but they crash the coverage generation.
                    "plugins/BadgifyComments",
                    "plugins/GoogleTranslateComments",
                    "plugins/PopularPosts",
                    "**/*.js",
                    ".git/**",
                ],
                reporter: ["json", "lcov"],
                reportsDirectory: coverageDir,
            },
        },
    };

    // Dev build
    return mergeConfig(commonConfig, ownConfig);
});
