/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import webpack, { Configuration } from "webpack";
import { VANILLA_ROOT } from "./env";
import { getOptions, BuildMode, getForumHotEntries } from "./utils";
import { makeBaseConfig } from "./makeBaseConfig";

export async function makeDevConfig() {
    const baseConfig: Configuration = (await makeBaseConfig()) as any;
    const forumEntries = await getForumHotEntries();

    console.log(forumEntries);

    // const middleWareEntry =;
    // require.resolve("webpack-hot-middleware/client") +
    // "?dynamicPublicPath=true" +
    // "&path=__webpack_hmr" +
    // "&reload=true";
    // const entry = [middleWareEntry, ...forumEntries];

    baseConfig.mode = "development";
    baseConfig.entry = forumEntries;
    baseConfig.output = {
        filename: `forum-hot-bundle.js`,
        chunkFilename: "[name].chunk.js",
        publicPath: `http://localhost:3030/`,
    };
    baseConfig.optimization!.splitChunks = false;

    return baseConfig;
}
