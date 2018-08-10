/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Configuration } from "webpack";
import { VANILLA_ROOT } from "./env";
import { getForumEntries, getOptions, BuildMode } from "./utils";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";
import UglifyJsPlugin from "uglifyjs-webpack-plugin";

export async function makeProdConfig() {
    const baseConfig: Configuration = (await makeBaseConfig()) as any;
    const forumEntries = await getForumEntries();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    baseConfig.output = {
        filename: "[name].min.js",
        chunkFilename: "[name].min.js",
        publicPath: "/",
        path: VANILLA_ROOT,
    };
    baseConfig.devtool = "source-map";
    baseConfig.optimization = {
        runtimeChunk: {
            name: "js/webpack/runtime",
        },
        splitChunks: {
            chunks: "initial",
            cacheGroups: {
                commons: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "js/webpack/vendors",
                    chunks: "initial",
                },
                library: {
                    test: /[\\/]applications[\\/]dashboard[\\/]src[\\/]scripts[\\/]/,
                    name: "js/webpack/library",
                    chunks: "all",
                    minChunks: 2,
                },
            },
        },
        minimizer: [
            new UglifyJsPlugin({
                cache: true,
                parallel: true,
                sourceMap: true, // set to true if you want JS source maps
            }),
        ],
    };

    if (getOptions().mode === BuildMode.ANALYZE) {
        baseConfig.plugins!.push(new BundleAnalyzerPlugin());
    }

    return baseConfig;
}
