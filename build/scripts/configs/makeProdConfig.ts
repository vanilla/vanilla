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
import OptimizeCSSAssetsPlugin from "optimize-css-assets-webpack-plugin";

let analyzePort = 8888;

/**
 * Create the production config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeProdConfig(section: string) {
    const baseConfig: Configuration = (await makeBaseConfig(section)) as any;
    const forumEntries = await getEntries(section);
    const options = await getOptions();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    // These outputs are expected to have the directory of the addon they belong to in their "[name]".
    // Webpack does not along a function for name here.
    baseConfig.output = {
        filename: "[name].min.js",
        chunkFilename: "[name].min.js?[chunkhash]",
        publicPath: "/",
        path: VANILLA_ROOT,
        library: `vanilla${section}`,
    };
    baseConfig.devtool = "source-map";
    baseConfig.optimization = {
        namedModules: false,
        namedChunks: false,
        // Create a single runtime chunk per section.
        runtimeChunk: {
            name: `js/webpack/runtime-${section}`,
        },
        // We want to split
        splitChunks: {
            chunks: "initial",
            minSize: 1000000, // This should prevent webpack from creating extra chunks for
            cacheGroups: {
                commons: {
                    test: /[\\/]node_modules[\\/]/,
                    minSize: 30000,
                    reuseExistingChunk: true,
                    name: `js/webpack/vendors-${section}`,
                    chunks: "initial",
                },
                library: {
                    // Our library files currently only come from the dashboard.
                    test: /[\\/]library[\\/]src[\\/]scripts[\\/]/,
                    minSize: 30000,
                    name: `applications/dashboard/js/webpack/library-${section}`,
                    // We currently NEED every library file to be shared among everything.
                    // Many of these files have common global state that is not exposed on the window object.
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
            new OptimizeCSSAssetsPlugin({}),
        ],
    };

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
