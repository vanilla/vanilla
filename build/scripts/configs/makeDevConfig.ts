/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import EntryModel from "../utility/EntryModel";
import { getOptions } from "../options";

/**
 * Create the development config. Eg. Hot build.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeDevConfig(entryModel: EntryModel, section: string) {
    const options = await getOptions();
    const { phpConfig } = options;
    const ip = phpConfig.HotReload && phpConfig.HotReload.IP ? phpConfig.HotReload.IP : "localhost";

    const baseConfig: Configuration = await makeBaseConfig(entryModel, section);
    const sectionEntries = await entryModel.getDevEntries(section);
    baseConfig.mode = "development";
    baseConfig.entry = sectionEntries;
    baseConfig.devtool = "cheap-module-eval-source-map";
    baseConfig.output = {
        filename: `${section}-hot-bundle.js`,
        chunkFilename: "[name].chunk.js",
        publicPath: `http://${ip}:3030/`,
    };
    baseConfig.optimization = {
        splitChunks: false,
    };

    return baseConfig;
}
