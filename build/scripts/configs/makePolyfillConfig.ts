/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import { POLYFILL_SOURCE_FILE, TS_CONFIG_FILE, VANILLA_ROOT } from "../env";

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
