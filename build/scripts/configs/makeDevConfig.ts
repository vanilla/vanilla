/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const ReactRefreshPlugin = require("@pmmmwh/react-refresh-webpack-plugin");
import webpack, { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import EntryModel from "../utility/EntryModel";
import { getOptions } from "../buildOptions";

/**
 * Create the development config. Eg. Hot build.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeDevConfig(entryModel: EntryModel, section: string) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, section, false);
    const sectionEntries = await entryModel.getDevEntries(section);
    baseConfig.mode = "development";
    baseConfig.entry = sectionEntries;
    baseConfig.devtool = "cheap-module-source-map";
    baseConfig.output = {
        filename: `${section}-hot-bundle.js`,
        chunkFilename: `[name]-[chunkhash]-${section}.chunk.js`,
        publicPath: `https://webpack.vanilla.localhost:3030/`,
    };

    baseConfig.plugins!.push(new webpack.HotModuleReplacementPlugin());
    baseConfig.plugins!.push(
        new ReactRefreshPlugin({
            forceEnable: true,
        }),
    );
    baseConfig.plugins!.push(
        new webpack.DefinePlugin({
            "process.env.NODE_ENV": JSON.stringify("development"),
        }),
    );

    return baseConfig;
}
