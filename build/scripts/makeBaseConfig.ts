/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import * as path from "path";
import { VANILLA_ROOT, TS_CONFIG_FILE, TS_LINT_FILE } from "./env";
import { getAddonAliasMapping, getScriptSourceFiles, lookupAddonPaths } from "./utils";
import HappyPack from "happypack";
import ForkTsCheckerWebpackPlugin from "fork-ts-checker-webpack-plugin";

export async function makeBaseConfig() {
    const happyThreadPool = HappyPack.ThreadPool({ size: 4, id: "scripts" });
    const addonPaths = await lookupAddonPaths();

    const modulePaths = [
        "node_modules",
        path.join(VANILLA_ROOT, "node_modules"),
        ...addonPaths.map(dir => path.resolve(dir, "node_modules")),
    ];
    const moduleAliases = await getAddonAliasMapping();

    return {
        context: VANILLA_ROOT,
        optimization: {
            splitChunks: {
                // include all types of chunks
                chunks: "all",
            },
        },
        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    exclude: ["node_modules"],
                    include: [
                        // We need to transpile quill's ES6 because we are building from source.
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
                    include: await getScriptSourceFiles(),
                    use: [
                        {
                            loader: "happypack/loader?id=ts",
                        },
                    ],
                },
                {
                    test: /\.html$/,
                    use: "raw-loader",
                },
                {
                    test: /\.svg$/,
                    use: [
                        {
                            loader: "html-loader",
                            options: {
                                minimize: true,
                            },
                        },
                    ],
                },
            ],
        },
        plugins: [
            new HappyPack({
                id: "ts",
                // verbose: options.verbose,
                threadPool: happyThreadPool,
                loaders: [
                    {
                        path: "ts-loader",
                        query: {
                            happyPackMode: true,
                            configFile: TS_CONFIG_FILE,
                        },
                    },
                ],
            }),
            new ForkTsCheckerWebpackPlugin({
                tsconfig: TS_CONFIG_FILE,
                // tslint: false,
                checkSyntacticErrors: true,
                async: true,
            }),
        ],
        resolve: {
            modules: modulePaths,
            alias: moduleAliases,
            extensions: [".ts", ".tsx", ".js", ".jsx"],
        },
        /**
         * We need to manually tell webpack where to resolve our loaders.
         * This is because process.cwd() probably won't contain the loaders we need
         * We are expecting thirs tool to be used in a different directory than itself.
         */
        resolveLoader: {
            modules: [path.join(VANILLA_ROOT, "node_modules")],
        },
    };
}
