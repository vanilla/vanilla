/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path, { dirname, join } from "path";
import fs from "fs";
import { globbySync } from "globby";
import type { StorybookConfig } from "@storybook/react-vite";
import { makeViteCommonConfig } from "../vite.commonConfig";
import reactPlugin from "@vitejs/plugin-react-swc";

const VANILLA_ROOT = path.resolve(__dirname, "../../");

const isProd = process.env.NODE_ENV === "production";
const sectionEnv = process.env.STORYBOOK_SECTION;
const hasModern = !sectionEnv || sectionEnv === "modern";
const hasFoundation = !sectionEnv || sectionEnv === "foundation";
const hasUI = !sectionEnv || sectionEnv === "ui";

let globs: string[] = [];

function scanAddons(addonDir) {
    const keys = globbySync(path.join(VANILLA_ROOT, addonDir + "/*"), { onlyDirectories: true }).map((dir) =>
        dir.replace(path.join(VANILLA_ROOT, addonDir + "/"), ""),
    );

    keys.forEach((key) => {
        let root = path.join(VANILLA_ROOT, addonDir, key);
        let addonRoot = path.join(root, "src");
        if (fs.existsSync(addonRoot)) {
            addonRoot = fs.realpathSync(addonRoot);
            globs.push(path.resolve(addonRoot, "**/*.story.@(ts|js|jsx|tsx|mdx)"));
        }
    });
}

if (hasModern) {
    globs.push(path.resolve(VANILLA_ROOT, "library/src/**/*.story.@(ts|js|jsx|tsx|mdx)"));
    scanAddons("applications");
    scanAddons("plugins");
    globs.push(path.resolve(__dirname, "../.storybookAppPages/*.story.jsx"));
}
if (hasUI) {
    globs.push(path.resolve(VANILLA_ROOT, "packages/vanilla-ui-library/**/*.story.@(ts|js|jsx|tsx|mdx)"));
    globs.push(path.resolve(VANILLA_ROOT, "packages/vanilla-ui/src/**/*.story.@(ts|js|jsx|tsx|mdx)"));
}

globs = globs.filter((globToFilter) => {
    return globbySync(globToFilter).length > 0;
});

const config: StorybookConfig = {
    stories: globs,
    addons: [
        {
            name: "@storybook/addon-essentials",
        },
    ],

    typescript: {
        check: false,
        reactDocgen: false,
    },

    async viteFinal(config) {
        const { mergeConfig } = await import("vite");
        // Merge custom configuration into the default config
        const commonConfig = makeViteCommonConfig();
        commonConfig.plugins!.push(
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
        );
        const finalConfig = mergeConfig(commonConfig, config);
        return finalConfig;
    },

    framework: getAbsolutePath("@storybook/react-vite"),

    docs: {},
    core: {
        disableTelemetry: true,
    },
};

export default config;

function getAbsolutePath(value: string): any {
    return dirname(require.resolve(join(value, "package.json")));
}
