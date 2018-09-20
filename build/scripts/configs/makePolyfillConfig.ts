/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import { POLYFILL_SOURCE_FILE, TS_CONFIG_FILE, DIST_DIRECTORY } from "../env";
import EntryModel from "../utility/EntryModel";

/**
 * Create a config for building the polyfills file. This should be built entirely on its own.
 */
export async function makePolyfillConfig(entryModel: EntryModel) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, "");
    baseConfig.mode = "production";
    baseConfig.devtool = "source-map";
    baseConfig.entry = POLYFILL_SOURCE_FILE;
    baseConfig.output = {
        filename: `polyfills.min.js`,
        path: DIST_DIRECTORY,
    };
    baseConfig.optimization = {
        splitChunks: false,
    };

    return baseConfig;
}
