/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import chalk from "chalk";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import * as path from "path";
import PrettierPlugin from "prettier-webpack-plugin";
import webpack from "webpack";
import WebpackBar from "webpackbar";
import { BuildMode, getOptions } from "../buildOptions";
import { PRETTIER_FILE, VANILLA_ROOT } from "../env";
import EntryModel from "../utility/EntryModel";
import { printVerbose } from "../utility/utils";
const CircularDependencyPlugin = require("circular-dependency-plugin");

/**
 * Create the core webpack config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeBaseConfig(entryModel: EntryModel, section: string) {
    const options = await getOptions();

    const modulePaths = [
        "node_modules",
        ...entryModel.addonDirs.map((dir) => path.resolve(dir, "node_modules")),
        path.join(VANILLA_ROOT, "node_modules"),
    ];

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

    // Leaving this out until we get the docs actually generating. Huge slowdown.
    // const storybookLoaders = section === "storybook" ? [require.resolve("react-docgen-typescript-loader")] : [];
    const storybookLoaders: never[] = [];

    const config: any = {
        context: VANILLA_ROOT,
        module: {
            rules: [
                {
                    test: /\.(jsx?|tsx?)$/,
                    exclude: (modulePath: string) => {
                        const modulesRequiringTranspilation = ["quill", "p-debounce", "@vanilla/.*"];
                        const exclusionRegex = new RegExp(`node_modules/(${modulesRequiringTranspilation.join("|")})/`);

                        if (modulePath.includes("core-js")) {
                            return true;
                        }

                        if (modulePath.includes("swagger-ui-react")) {
                            // Do not do additional transpilation of swagger-ui.
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
                                presets: [require.resolve("@vanilla/babel-preset")],
                                plugins: babelPlugins,
                                cacheDirectory: true,
                            },
                        },
                        ...storybookLoaders,
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
                        BuildMode.PRODUCTION === options.mode
                            ? MiniCssExtractPlugin.loader
                            : {
                                  loader: "style-loader",
                                  options: {
                                      injectType: "singletonStyleTag",
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
                                config: {
                                    path: path.resolve(__dirname),
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
            mainFields: ["browser", "main"],
            alias: {
                ...hotAliases,
                ...entryModel.aliases,
                "library-scss": path.resolve(VANILLA_ROOT, "library/src/scss"),
                "react-select": require.resolve("react-select/dist/react-select.esm.js"),
                typestyle: path.resolve(VANILLA_ROOT, "library/src/scripts/styles/styleShim.ts"),
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
                filename: "[name].min.css?[chunkhash]",
            }),
        );
    }

    if (options.fix) {
        config.plugins.unshift(getPrettierPlugin());
    }
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
