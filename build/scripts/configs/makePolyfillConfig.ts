/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import { POLYFILL_SOURCE_FILE, TS_CONFIG_FILE, VANILLA_ROOT } from "../env";

/**
 * Create a config for building the polyfills file. This should be built entirely on its own.
 */
export async function makePolyfillConfig() {
    const baseConfig: Configuration = (await makeBaseConfig("")) as any;
    baseConfig.mode = "production";
    baseConfig.devtool = "source-map";
    baseConfig.entry = POLYFILL_SOURCE_FILE;
    baseConfig.output = {
        filename: `js/webpack/polyfills.min.js`,
        path: VANILLA_ROOT,
    };
    baseConfig.module!.rules = [
        {
            test: /\.tsx?$/,
            exclude: ["node_modules"],
            use: [
                {
                    loader: "ts-loader",
                    options: {
                        configFile: TS_CONFIG_FILE,
                    },
                },
            ],
        },
    ];
    baseConfig.optimization = {
        splitChunks: false,
    };

    return baseConfig;
}
