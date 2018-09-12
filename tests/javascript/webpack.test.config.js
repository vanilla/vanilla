/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const path = require("path");
const webpack = require("webpack");

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
                        loader: "babel-loader",
                        options: {
                            presets: ["@vanillaforums/babel-preset"],
                            cacheDirectory: true,
                        },
                    },
                    {
                        loader: "ts-loader",
                        options: {
                            configFile: path.resolve(VANILLA_ROOT, "tsconfig.json"),
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
        alias: {
            "@library": path.resolve(VANILLA_ROOT, "library/src/scripts/"),
            "@dashboard": path.resolve(VANILLA_ROOT, "applications/dashboard/src/scripts/"),
            "@vanilla": path.resolve(VANILLA_ROOT, "applications/vanilla/src/scripts/"),
            "@rich-editor": path.resolve(VANILLA_ROOT, "plugins/rich-editor/src/scripts/"),
            "@testroot": path.resolve(VANILLA_ROOT, "tests/javascript/"),
        },
        extensions: [".ts", ".tsx", ".js", ".jsx"],
    },
    plugins: [
        // For some reason karma-webpack doesn't pass along the sourcemaps properly for files with .ts extensions
        // https://stackoverflow.com/a/39175635
        new webpack.SourceMapDevToolPlugin({
            filename: null, // if no value is provided the sourcemap is inlined
            test: /\.(ts|tsx|js|jsx)($|\?)/i, // process .js and .ts files only
        }),
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
