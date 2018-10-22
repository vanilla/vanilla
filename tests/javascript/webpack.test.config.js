/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const path = require("path");
const webpack = require("webpack");
const TsConfigPathsPlugin = require('tsconfig-paths-webpack-plugin');

const VANILLA_ROOT = path.resolve(path.join(__dirname, "../../"));
const TS_CONFIG_FILE = path.resolve(VANILLA_ROOT, "tsconfig.json");

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
                    /\/node_modules\/quill/,
                ],
                use: [
                    {
                        loader: "babel-loader",
                        options: {
                            presets: ["@vanillaforums/babel-preset"],
                            cacheDirectory: true,
                        },
                    },
                ],
            },
            {
                test: /\.tsx?$/,
                exclude: ["node_modules"],
                include: [/\/src\/scripts/, /tests\/javascript/],
                use: [
                    {
                        loader: "ts-loader",
                        options: {
                            configFile: TS_CONFIG_FILE,
                            transpileOnly: true,
                        },
                    },
                ],
            },
            {
                test: /\.html$/,
                use: "raw-loader",
            },
        ],
    },
    resolve: {
        modules: [
            path.join(VANILLA_ROOT, "node_modules"),
            path.join(VANILLA_ROOT, "plugins/rich-editor/node_modules"),
            path.join(VANILLA_ROOT, "tests/node_modules"),
        ],
        plugins: [
            new TsConfigPathsPlugin({configFile: TS_CONFIG_FILE}),
        ],
        extensions: [".ts", ".tsx", ".js", ".jsx"],
    },
    plugins: [
        new webpack.DefinePlugin({ VANILLA_ROOT }),
    ],

    /**
     * We need to manually tell webpack where to resolve our loaders.
     * This is because process.cwd() probably won't contain the loaders we need
     * We are expecting thirs tool to be used in a different directory than itself.
     */
    resolveLoader: {
        modules: [path.join(VANILLA_ROOT, "node_modules")],
    },
};
