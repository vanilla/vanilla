/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import { POLYFILL_SOURCE_FILE, DIST_DIRECTORY } from "../env";
import TerserWebpackPlugin from "terser-webpack-plugin";
import EntryModel from "../utility/EntryModel";
import { getOptions } from "../buildOptions";

/**
 * Create a config for building the polyfills file. This should be built entirely on its own.
 */
export async function makePolyfillConfig(entryModel: EntryModel) {
    const options = await getOptions();
    const baseConfig: Configuration = await makeBaseConfig(entryModel, "polyfill");
    baseConfig.mode = "production";
    baseConfig.devtool = false;
    baseConfig.entry = POLYFILL_SOURCE_FILE;
    baseConfig.target = ["web", "es5"];
    baseConfig.output = {
        filename: `polyfills.min.js`,
        path: DIST_DIRECTORY,
    };
    baseConfig.optimization = {
        splitChunks: false,
        minimizer: options.debug ? [] : [new TerserWebpackPlugin()],
    };

    return baseConfig;
}
