/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import path from "path";
import webpack, { Configuration } from "webpack";
import { DIST_DIRECTORY } from "../env";
import { getOptions, BuildMode } from "../options";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";
import UglifyJsPlugin from "uglifyjs-webpack-plugin";
import OptimizeCSSAssetsPlugin from "optimize-css-assets-webpack-plugin";
import EntryModel from "../utility/EntryModel";

let analyzePort = 8888;

/**
 * Create the production config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeProdConfig(entryModel: EntryModel, section: string) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, section);
    const forumEntries = await entryModel.getProdEntries(section);
    const options = await getOptions();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    // These outputs are expected to have the directory of the addon they belong to in their "[name]".
    // Webpack does not along a function for name here.
    baseConfig.output = {
        filename: "[name].min.js",
        chunkFilename: "[name].min.js?[chunkhash]",
        publicPath: `/dist/${section}`,
        path: path.join(DIST_DIRECTORY, section),
        library: `vanilla${section}`,
    };
    baseConfig.devtool = "source-map";
    baseConfig.optimization = {
        noEmitOnErrors: true,
        namedModules: false,
        namedChunks: false,
        // Create a single runtime chunk per section.
        runtimeChunk: {
            name: `runtime`,
        },
        // We want to split
        splitChunks: {
            chunks: "all",
            minSize: 10000000, // This should prevent webpack from creating extra chunks.
            cacheGroups: {
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    minSize: 30000,
                    reuseExistingChunk: true,
                    // If name is explicitly specified many different vendors~someOtherChunk combined
                    // chunk bundles will get outputted.
                    name: "vendors",
                    chunks: "all",
                    minChunks: 2,
                },
                shared: {
                    // Our library files currently only come from the dashboard.
                    test: /[\\/]library[\\/]src[\\/]scripts[\\/]/,
                    minSize: 30000,
                    // If name is explicitly specified many different shared~someOtherChunk combined
                    // chunk bundles will get outputted.
                    name: "shared",
                    // We currently NEED every library file to be shared among everything.
                    // Many of these files have common global state that is not exposed on the window object.
                    chunks: "all",
                    minChunks: 2,
                },
                // Allow async chunks to be split however webpack wants to split them.
                async: {
                    minSize: 50000,
                    chunks: "async",
                },
            },
        },
        minimizer: [
            new UglifyJsPlugin({
                cache: true,
                parallel: true,
                sourceMap: true, // set to true if you want JS source maps
            }),
            new OptimizeCSSAssetsPlugin({}),
        ],
    };

    baseConfig.plugins!.push(
        new webpack.DefinePlugin({
            __BUILD__SECTION__: JSON.stringify(section),
        }),
    );

    // Spawn a bundle size analyzer. This is super usefull if you find a bundle has jumped up in size.
    if (options.mode === BuildMode.ANALYZE) {
        baseConfig.plugins!.push(
            new BundleAnalyzerPlugin({
                analyzerPort: analyzePort,
            }),
        );
        analyzePort++;
    }

    return baseConfig;
}
