/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const ReactRefreshPlugin = require("react-refresh-webpack-plugin");
import webpack, { Configuration } from "webpack";
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

    const baseConfig: Configuration = await makeBaseConfig(entryModel, section);
    const sectionEntries = await entryModel.getDevEntries(section);
    baseConfig.mode = "development";
    baseConfig.entry = sectionEntries;
    baseConfig.devtool = "eval-source-map";
    baseConfig.output = {
        filename: `${section}-hot-bundle.js`,
        chunkFilename: `[name]-[chunkhash]-${section}.chunk.js`,
        publicPath: `http://${options.devIp}:3030/`,
    };
    baseConfig.optimization = {
        namedModules: true,
        namedChunks: true,
        splitChunks: false,
    };
    baseConfig.plugins!.push(new webpack.HotModuleReplacementPlugin());
    baseConfig.plugins!.push(new ReactRefreshPlugin());

    return baseConfig;
}
