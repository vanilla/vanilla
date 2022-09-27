/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import webpack from "webpack";
import { DIST_DIRECTORY, DIST_NAME, LIBRARY_SRC_DIRECTORY, VANILLA_ROOT } from "../env";
import path from "path";
import WebpackBar from "webpackbar";
import TerserWebpackPlugin from "terser-webpack-plugin";
import { notEmpty } from "@vanilla/utils";
import { makeManifestPlugin } from "./makeManifestPlugin";

export function makeEmbedConfig(isProd: boolean): webpack.Configuration {
    return {
        entry: {
            "modernEmbed.remote": path.join(VANILLA_ROOT, "library/src/scripts/embed/modernEmbed.remote.ts"),
        },
        optimization: {
            splitChunks: false,
        },
        output: isProd
            ? {
                  publicPath: `/${DIST_NAME}/embed/`,
                  library: `vanillaEmbed`,
                  path: path.join(DIST_DIRECTORY, "embed"),
                  filename: "[name].[contenthash].min.js",
              }
            : {
                  filename: `embed-hot-bundle.js`,
                  publicPath: `https://webpack.vanilla.localhost:3030/`,
              },
        resolve: {
            alias: {
                "@library": LIBRARY_SRC_DIRECTORY,
            },
        },
        module: {
            rules: [
                {
                    test: /\.(m?jsx?|tsx?)$/,
                    use: [
                        {
                            loader: "swc-loader",
                        },
                    ],
                },
            ],
        },
        mode: isProd ? "production" : "development",
        devtool: isProd ? false : "cheap-module-source-map",
        plugins: [
            new WebpackBar({
                name: "embed",
            }),
            isProd ? makeManifestPlugin() : null,
            isProd
                ? new TerserWebpackPlugin({
                      // SWC offers a much faster minimizer than the default.
                      minify: TerserWebpackPlugin.swcMinify,
                      terserOptions: {
                          ecma: 2015,
                          format: {
                              comments: false,
                          },
                      },
                      extractComments: false,
                  })
                : null,
        ].filter(notEmpty),
    };
}
