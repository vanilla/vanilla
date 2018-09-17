/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as path from "path";
import { VANILLA_ROOT, TS_CONFIG_FILE, TS_LINT_FILE, PRETTIER_FILE } from "../env";
import { getAddonAliasMapping, getScriptSourceDirectories, lookupAddonPaths } from "../utility/addonUtils";
import PrettierPlugin from "prettier-webpack-plugin";
import HappyPack from "happypack";
import ForkTsCheckerWebpackPlugin from "fork-ts-checker-webpack-plugin";
import { getOptions, BuildMode } from "../options";
import chalk from "chalk";
import { printVerbose } from "../utility/utils";
import MiniCssExtractPlugin from "mini-css-extract-plugin";

/**
 * Create the core webpack config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeBaseConfig(section: string) {
    const happyThreadPool = HappyPack.ThreadPool({ size: 4, id: "ts" });
    const addonPaths = await lookupAddonPaths(section);
    const options = await getOptions();

    const modulePaths = [
        "node_modules",
        path.join(VANILLA_ROOT, "node_modules"),
        ...addonPaths.map(dir => path.resolve(dir, "node_modules")),
    ];
    const moduleAliases = await getAddonAliasMapping(section);

    const aliases = Object.keys(moduleAliases).join(", ");
    const message = `Building section ${chalk.yellowBright(section)} with the following aliases
${chalk.green(aliases)}`;
    printVerbose(message);

    const tsSourceIncludes = await getScriptSourceDirectories(section);

    const extraTsLoaders =
        options.mode === BuildMode.DEVELOPMENT
            ? [
                  {
                      loader: "babel-loader",
                      options: {
                          babelrc: false,
                          plugins: [
                              require.resolve("react-hot-loader/babel"),
                              require.resolve("babel-plugin-syntax-dynamic-import"),
                          ],
                      },
                  },
              ]
            : [];

    const config = {
        context: VANILLA_ROOT,
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
                                presets: [require.resolve("@vanillaforums/babel-preset")],
                                cacheDirectory: true,
                            },
                        },
                    ],
                },
                {
                    test: /\.tsx?$/,
                    exclude: ["node_modules"],
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
                {
                    test: /\.s?css$/,
                    use: [
                        options.mode === BuildMode.DEVELOPMENT ? "style-loader" : MiniCssExtractPlugin.loader,
                        {
                            loader: "css-loader",
                            options: {
                                sourceMap: true,
                            },
                        },
                        {
                            loader: "postcss-loader",
                            options: {
                                sourceMap: true,
                                config: {
                                    path: path.resolve(__dirname),
                                },
                            },
                        },
                        {
                            loader: "sass-loader",
                            options: {
                                sourceMap: true,
                            },
                        },
                    ],
                },
            ],
        },
        plugins: [
            new HappyPack({
                id: "ts",
                verbose: options.verbose,
                threadPool: happyThreadPool,
                loaders: [
                    ...extraTsLoaders,
                    {
                        loader: "ts-loader",
                        options: {
                            happyPackMode: true,
                            configFile: TS_CONFIG_FILE,
                        },
                    },
                ],
            }),
            new ForkTsCheckerWebpackPlugin({
                tsconfig: TS_CONFIG_FILE,
                tslint: TS_LINT_FILE,
                checkSyntacticErrors: true,
                async: true,
            }),
        ],
        resolve: {
            modules: modulePaths,
            alias: {
                ...moduleAliases,
                "library-scss": path.resolve(VANILLA_ROOT, "library/src/scss"),
            },
            extensions: [".ts", ".tsx", ".js", ".jsx"],
            // This needs to be true so that the same copy of a node_module gets shared.
            // Ex. If quill has parchment as a dep and imports and we use parchment too, there will be two paths
            // - node_modules/quill/node_modules/parchment
            // - node_modules/parchment
            // The quill one is a symlinked one so we need webpack to resolve these to the same filepath.
            symlinks: true,
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

    if (options.mode === BuildMode.PRODUCTION) {
        config.plugins.push(
            new MiniCssExtractPlugin({
                filename: "[name].min.css",
            }),
        );
    }

    if (options.fix) {
        config.plugins.unshift(getPrettierPlugin());
    }

    return config;
}

/**
 * Get a prettier plugin instance. This will autoformat source code as its built.
 */
function getPrettierPlugin() {
    const prettierConfig = require(PRETTIER_FILE);
    return new PrettierPlugin({
        ...prettierConfig,
        parser: "typescript",
        extensions: [".ts", ".tsx"],
    });
}
