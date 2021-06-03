/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path from "path";
import { Configuration } from "webpack";
import { DIST_DIRECTORY } from "../env";
import { getOptions, BuildMode } from "../buildOptions";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";
import TerserWebpackPlugin from "terser-webpack-plugin";
import EntryModel from "../utility/EntryModel";
import CssMinimizerPlugin from "css-minimizer-webpack-plugin";
const { WebpackManifestPlugin } = require("webpack-manifest-plugin");

let analyzePort = 8888;

/**
 * Create the production config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeProdConfig(entryModel: EntryModel, section: string, isLegacy: boolean = true) {
    const baseConfig: Configuration = await makeBaseConfig(entryModel, section, isLegacy);
    const forumEntries = await entryModel.getProdEntries(section);
    const options = await getOptions();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    baseConfig.devtool = false;
    baseConfig.target = ["web", "es5"];
    if (options.modern) {
        baseConfig.target = isLegacy ? ["web", "es5"] : ["web"];
    }
    // These outputs are expected to have the directory of the addon they beng to in their "[name]".
    // Webpack does not along a function for name here.
    baseConfig.output = {
        filename: "[name].[contenthash].min.js",
        chunkFilename: "async/[name].[contenthash].min.js",
        publicPath: `/dist/${section}/`,
        path: path.join(DIST_DIRECTORY, section),
        library: `vanilla${section}`,
    };
    if (options.modern) {
        baseConfig.output.publicPath = isLegacy ? `/dist/${section}/` : `/dist/${section}-modern/`;
        baseConfig.output.path = path.join(DIST_DIRECTORY, isLegacy ? section : `${section}-modern`);
    }
    baseConfig.optimization = {
        emitOnErrors: false,
        chunkIds: options.debug ? "named" : undefined,
        moduleIds: options.debug ? "named" : undefined,
        // Create a single runtime chunk per section.
        runtimeChunk: {
            name: `runtime`,
        },
        // We want to split
        splitChunks: {
            usedExports: true,
            maxInitialSize: 200000,
            maxInitialRequests: 20,
            chunks: "all",
            // minSize: 10000000, // This should prevent webpack from creating extra chunks.
            cacheGroups: {
                library: {
                    test: /[\\/]library[\\/]/,
                    name: "library",
                    chunks: "initial",
                    reuseExistingChunk: true,
                },
                packages: {
                    test: /[\\/]packages\/vanilla[\\/]/,
                    name: "packages",
                    chunks: "initial",
                    reuseExistingChunk: true,
                },
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: -10,
                    name: "vendors",
                    chunks: "initial",
                    reuseExistingChunk: true,
                },
                react: {
                    test: /[\\/]node_modules[\\/](react|react-dom|react-redux|redux)[\\/]/,
                    name: "react",
                    chunks: "all",
                    priority: -5,
                },
                swagger: {
                    test: /[\\/]node_modules[\\/]swagger-ui.*/,
                    name: "swagger-ui",
                    chunks: "all",
                    priority: -1,
                },
            },
        },
        minimize: !options.debug,
        minimizer: options.debug
            ? []
            : ([
                  new TerserWebpackPlugin({
                      parallel: options.lowMemory ? 1 : true,
                      terserOptions: {
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

    baseConfig.plugins?.push(new WebpackManifestPlugin({}));

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
