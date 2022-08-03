/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path from "path";
import { Configuration } from "webpack";
import { DIST_DIRECTORY, DIST_NAME } from "../env";
import { getOptions, BuildMode } from "../buildOptions";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";
import TerserWebpackPlugin from "terser-webpack-plugin";
import EntryModel from "../utility/EntryModel";
import CssMinimizerPlugin from "css-minimizer-webpack-plugin";
import { makeManifestPlugin } from "./makeManifestPlugin";

let analyzePort = 8888;

/**
 * Create the production config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeProdConfig(entryModel: EntryModel, section: string) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, section);
    const prodEntries = await entryModel.getProdEntries(section);
    const options = await getOptions();

    baseConfig.mode = "production";
    baseConfig.entry = prodEntries;
    baseConfig.devtool = false;
    baseConfig.target = ["web"];
    // These outputs are expected to have the directory of the addon they beng to in their "[name]".
    // Webpack does not along a function for name here.
    baseConfig.output = {
        filename: "[name].[contenthash].min.js",
        chunkFilename: "async/[name].[contenthash].min.js",
        publicPath: `/${DIST_NAME}/${section}/`,
        path: path.join(DIST_DIRECTORY, section),
        library: `vanilla${section.replace("-", "_")}`,
    };
    baseConfig.optimization = {
        emitOnErrors: false,
        chunkIds: options.debug ? "named" : undefined,
        moduleIds: options.debug ? "named" : undefined,
        // Create a single runtime chunk.
        runtimeChunk: {
            name: `runtime`,
        },
        // Split up chunks.
        splitChunks: {
            // All of our addon chunks are async so we need to use "all" instead of "initial",
            chunks: "all",
            cacheGroups: {
                defaultVendors: false,
                // The library chunk ensures that commonly used parts of library are shared between addons.
                library: {
                    test: /[\\/](library)[\\/]/,
                    idHint: "library",
                    name: "library",
                    chunks: "all",
                    maxSize: 500000,
                    minRemainingSize: 50000,
                    priority: 1,
                },
                // Packages is similar to library
                // This one is configured a little differently because
                // webpack kept duplicating `@vanilla/icons` in addon chunks.
                packages: {
                    test: /[\\/]packages[\\/]vanilla/,
                    idHint: "packages",
                    name: "packages",
                    reuseExistingChunk: false,
                    priority: 100000,
                    enforce: true,
                },
                // Increase the minimum size of the default chunk splitting.
                // Webpack doesn't take gzip into account when measuring chunk sizes.
                default: {
                    minSize: 50000,
                },
                // Put our most stable vendor files into their own chunks.
                vendors: {
                    test: /[\\/]node_modules[\\/](@emotion|react|react-dom|react-redux|redux|react-loadable|lodash)[\\/]/,
                    name: "vendors",
                    chunks: "all",
                    priority: 10,
                    enforce: true,
                    reuseExistingChunk: false,
                },
            },
        },
        minimize: !options.debug,
        minimizer: options.debug
            ? []
            : ([
                  new TerserWebpackPlugin({
                      parallel: options.lowMemory ? 1 : true,
                      // SWC offers a much faster minimizer than the default.
                      minify: TerserWebpackPlugin.swcMinify,
                      terserOptions: {
                          ecma: 2015,
                          format: {
                              comments: false,
                          },
                      },
                      extractComments: false,
                  }),
                  new CssMinimizerPlugin({
                      parallel: options.lowMemory ? 1 : true,
                      minify: (CssMinimizerPlugin as any).cssoMinify,
                      minimizerOptions: {
                          preset: [
                              "default",
                              {
                                  discardComments: { removeAll: true },
                              },
                          ],
                      },
                  }),
              ] as any),
    };

    baseConfig.plugins?.push(makeManifestPlugin());

    // Spawn a bundle size analyzer. This is super usefull if you find a bundle has jumped up in size.
    if (options.mode === BuildMode.ANALYZE) {
        baseConfig.plugins!.push(
            new BundleAnalyzerPlugin({
                analyzerPort: analyzePort,
            }) as any,
        );
        analyzePort++;
    }

    return baseConfig;
}
