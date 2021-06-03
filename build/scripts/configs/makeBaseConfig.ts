/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import chalk from "chalk";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import * as path from "path";
import { svgLoader } from "./svgLoader";
import webpack from "webpack";
import WebpackBar from "webpackbar";
import { BuildMode, getOptions } from "../buildOptions";
import { VANILLA_ROOT } from "../env";
import EntryModel from "../utility/EntryModel";
import { printVerbose } from "../utility/utils";
const CircularDependencyPlugin = require("circular-dependency-plugin");
import globby from "globby";

/**
 * Create the core webpack config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeBaseConfig(entryModel: EntryModel, section: string, isLegacy: boolean = true) {
    const options = await getOptions();

    const customModulePaths = [
        ...entryModel.addonDirs.map((dir) => path.resolve(dir, "node_modules")),
        path.join(VANILLA_ROOT, "node_modules"),
    ];

    const modulePaths = ["node_modules", ...customModulePaths];

    const aliases = Object.keys(entryModel.aliases).join(", ");
    const message = `Building section ${chalk.yellowBright(section)} with the following aliases
${chalk.green(aliases)}`;
    printVerbose(message);

    const babelPlugins: any[] = [];
    const hotLoaders: any[] = [];
    const hotAliases: any = {};
    if (options.mode === BuildMode.DEVELOPMENT) {
        // This plugin has very flaky detection of env variables.
        // It can't seem to detect that we are no in production mode.
        // So we need to disable the ENV check.
        babelPlugins.push([require.resolve("react-refresh/babel"), { skipEnvCheck: true }]);
    }

    section = isLegacy ? section : `${section}-modern`;
    const config: any = {
        context: VANILLA_ROOT,
        parallelism: 50, // Intentionally brought down from 50 to reduce memory usage.
        cache: {
            type: "filesystem",
            allowCollectingMemory: true, // Required to keep memory usage down.
            buildDependencies: {
                config: [...globby.sync(path.resolve(__dirname, "*"))],
            },
            // This will cause cache inconsistencies if manually modifying these without
            // changing the package.json (which is used to avoid hashing node_modules).
            managedPaths: customModulePaths,
            name: `${section}-${options.mode}-${options.debug}`,
            maxMemoryGenerations: options.lowMemory ? 3 : Infinity,
        },
        module: {
            rules: [
                {
                    test: /\.(m?jsx?|tsx?)$/,
                    exclude: (modulePath: string) => {
                        const modulesRequiringTranspilation = [
                            "quill",
                            "p-debounce",
                            "@vanilla/.*",
                            "@monaco-editor/react.*",
                            "ajv.*",
                            "d3-.*",
                            "@reduxjs/toolkit.*",
                            "@?react-spring.*",
                            "delaunator.*",
                            "buffer",
                            "rafz",
                            "highlight.js",
                            "@reach/.*",
                            "react-markdown",
                            "@simonwep.*",
                            "swagger-ui-react",
                        ];
                        const exclusionRegex = new RegExp(`node_modules/(${modulesRequiringTranspilation.join("|")})/`);

                        if (modulePath.includes("core-js")) {
                            return true;
                        }

                        // We need to transpile quill's ES6 because we are building from source.
                        return /node_modules/.test(modulePath) && !exclusionRegex.test(modulePath);
                    },
                    use: [
                        ...hotLoaders,
                        {
                            loader: "babel-loader",
                            options: {
                                presets: [
                                    [
                                        require.resolve("@vanilla/babel-preset"),
                                        {
                                            isLegacy,
                                        },
                                    ],
                                ],
                                plugins: babelPlugins,
                                cacheDirectory: true,
                            },
                        },
                    ],
                },
                {
                    test: /\.html$/,
                    use: "raw-loader",
                },
                svgLoader(),
                { test: /\.(png|jpg|jpeg|gif)$/i, type: "asset/resource" },
                {
                    test: /\.s?css$/,
                    use: [
                        BuildMode.PRODUCTION === options.mode
                            ? MiniCssExtractPlugin.loader
                            : {
                                  loader: "style-loader",
                                  options: {
                                      insert: function insertAtTop(element: HTMLElement) {
                                          const staticStylesheets = document.head.querySelectorAll(
                                              'link[rel="stylesheet"][static="1"]',
                                          );
                                          const lastStaticStylesheet = staticStylesheets[staticStylesheets.length - 1];
                                          if (lastStaticStylesheet) {
                                              document.head.insertBefore(element, lastStaticStylesheet.nextSibling);
                                          } else {
                                              document.head.appendChild(element);
                                          }
                                      },
                                  },
                              },
                        {
                            loader: "css-loader",
                            options: {
                                sourceMap: true,
                                url: false,
                            },
                        },
                        {
                            loader: "postcss-loader",
                            options: {
                                sourceMap: true,
                                postcssOptions: {
                                    config: path.resolve(VANILLA_ROOT, "build/scripts/configs/postcss.config.js"),
                                    isLegacy,
                                },
                            },
                        },
                        {
                            loader: "sass-loader",
                            options: {
                                sourceMap: true,
                                implementation: require("sass"), // Use dart sass
                            },
                        },
                    ],
                },
            ],
        },
        performance: { hints: false },
        plugins: [
            new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /en/),
            new webpack.DefinePlugin({
                __BUILD__SECTION__: JSON.stringify(section),
            }),
        ] as any[],
        resolve: {
            modules: modulePaths,
            mainFields: ["browser", "module", "main"],
            alias: {
                ...hotAliases,
                ...entryModel.aliases,
                "library-scss": path.resolve(VANILLA_ROOT, "library/src/scss"),
                "react-select": require.resolve("react-select/dist/react-select.esm.js"),
                typestyle: path.resolve(VANILLA_ROOT, "library/src/scripts/styles/styleShim.ts"),
                // Legacy mapping that doesn't exist any more. Even has a lint rule against it.
                "@vanilla/library/src/scripts": path.resolve(VANILLA_ROOT, "library/src/scripts"),
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
                filename: "[name].[contenthash].min.css",
                chunkFilename: "async/[name].[contenthash].min.css",
            }),
        );
    }

    // Fix modules like swagger-ui that need buffer.
    // Webpack no-longer applies it automatically with webpack 5.
    // https://github.com/webpack/changelog-v5/issues/10#issuecomment-615877593
    config.plugins.push(
        new webpack.ProvidePlugin({
            Buffer: ["buffer", "Buffer"],
        }),
    );

    config.plugins.push(
        new WebpackBar({
            name: section,
        }),
    );

    if (options.circular) {
        config.plugins.push(
            new CircularDependencyPlugin({
                // exclude detection of files based on a RegExp
                exclude: /a\.js|node_modules|rich-editor/,
                // add errors to webpack instead of warnings
                failOnError: true,
                // allow import cycles that include an asyncronous import,
                // e.g. via import(/* webpackMode: "weak" */ './file.js')
                allowAsyncCycles: false,
                // set the current working directory for displaying module paths
                cwd: process.cwd(),
            }),
        );
    }

    return config;
}
