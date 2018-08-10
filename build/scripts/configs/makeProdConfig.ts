/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { Configuration } from "webpack";
import { VANILLA_ROOT } from "../env";
import { getEntries } from "../utility/addonUtils";
import { getOptions, BuildMode } from "../options";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";
import UglifyJsPlugin from "uglifyjs-webpack-plugin";

export async function makeProdConfig(section: string) {
    const baseConfig: Configuration = (await makeBaseConfig(section)) as any;
    const forumEntries = await getEntries(section);
    const options = await getOptions();

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
            name: `js/webpack/runtime-${section}`,
        },
        splitChunks: {
            chunks: "initial",
            cacheGroups: {
                commons: {
                    test: /[\\/]node_modules[\\/]/,
                    name: `js/webpack/vendors-${section}`,
                    chunks: "initial",
                },
                library: {
                    test: /[\\/]applications[\\/]dashboard[\\/]src[\\/]scripts[\\/]/,
                    name: `applications/dashboard/js/webpack/library-${section}`,
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

    if (options.mode === BuildMode.ANALYZE) {
        baseConfig.plugins!.push(new BundleAnalyzerPlugin());
    }

    return baseConfig;
}
