/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

const path = require("path");
const fs = require("fs");
const webpack = require("webpack");
const chalk = require("chalk");
const glob = require("glob");

const VANILLA_ROOT = path.resolve(path.join(__dirname, "../../"));

module.exports = {
    context: VANILLA_ROOT,
    mode: "development",
    devtool: "inline-source-map",
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: ["node_modules"],
                include: [
                    /\/src\/scripts/,
                    // We need to transpile quill's ES6 because we are building form source.
                    /\/node_modules\/quill/
                ],
                use: [
                    {
                        loader: "babel-loader",
                        options: {
                            presets: ["@vanillaforums/babel-preset"],
                            cacheDirectory: true
                        }
                    }
                ]
            },
            {
                test: /\.tsx?$/,
                exclude: ["node_modules"],
                include: [/\/src\/scripts/],
                use: [
                    {
                        loader: "ts-loader",
                        options: {
                            configFile: path.resolve(
                                VANILLA_ROOT,
                                "tsconfig.json"
                            ),
                            transpileOnly: true
                        }
                    }
                ]
            }
        ]
    },
    resolve: {
        modules: [
            path.join(VANILLA_ROOT, "applications/dashboard/node_modules"),
            path.join(VANILLA_ROOT, "applications/vanilla/node_modules"),
            path.join(VANILLA_ROOT, "tests/node_modules")
        ],
        alias: {
            "@dashboard": path.resolve(
                VANILLA_ROOT,
                "applications/dashboard/src/scripts/"
            ),
            "@vanilla": path.resolve(
                VANILLA_ROOT,
                "applicatons/vanilla/src/scripts/"
            )
        },
        extensions: [".ts", ".tsx", ".js", ".jsx"]
    },
    plugins: [
        new webpack.SourceMapDevToolPlugin({
            filename: null, // if no value is provided the sourcemap is inlined
            test: /\.(ts|js)($|\?)/i // process .js and .ts files only
        })
    ],

    /**
     * We need to manually tell webpack where to resolve our loaders.
     * This is because process.cwd() probably won't contain the loaders we need
     * We are expecting thirs tool to be used in a different directory than itself.
     */
    resolveLoader: {
        modules: [path.join(VANILLA_ROOT, "tests/node_modules")]
    }
};
