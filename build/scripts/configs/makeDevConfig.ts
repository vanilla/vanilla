/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Configuration } from "webpack";
import { getHotEntries } from "../utility/addonUtils";
import { makeBaseConfig } from "./makeBaseConfig";

/**
 * Create the development config. Eg. Hot build.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeDevConfig(section: string) {
    const baseConfig: Configuration = (await makeBaseConfig(section)) as any;
    const sectionEntries = await getHotEntries(section);
    baseConfig.mode = "development";
    baseConfig.entry = sectionEntries;
    baseConfig.devtool = "cheap-module-eval-source-map";
    baseConfig.output = {
        filename: `${section}-hot-bundle.js`,
        chunkFilename: "[name].chunk.js",
        publicPath: `http://localhost:3030/`,
    };
    baseConfig.optimization = {
        splitChunks: false,
    };

    return baseConfig;
}
