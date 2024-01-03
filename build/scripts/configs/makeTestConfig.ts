/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import webpack, { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import EntryModel from "../utility/EntryModel";

/**
 * Create a config for building the polyfills file. This should be built entirely on its own.
 */
export async function makeTestConfig(entryModel: EntryModel) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, "unit-tests");
    baseConfig.mode = "development";
    baseConfig.devtool = "inline-cheap-module-source-map";
    baseConfig.optimization = {
        minimize: false,
    };
    baseConfig.plugins?.push(
        new webpack.DefinePlugin({
            // Currently a warning with this. karma defines one of "development" giving a conflict warning.
            // We have quite a few things depending on this being "test".
            ["process.env.NODE_ENV"]: "'test'",
            ["process.env.IS_WEBPACK"]: true,
        }),
        // Shim node builtins for some tests in the browser.
        new webpack.ProvidePlugin({
            process: "process/browser",
        }),
    );

    return baseConfig;
}
