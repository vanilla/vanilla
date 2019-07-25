/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as path from "path";
import webpack from "webpack";
import { VANILLA_ROOT, PRETTIER_FILE } from "../env";
import PrettierPlugin from "prettier-webpack-plugin";
import { getOptions, BuildMode } from "../options";
import chalk from "chalk";
import { printVerbose } from "../utility/utils";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import EntryModel from "../utility/EntryModel";
import WebpackBar from "webpackbar";

/**
 * Create the core webpack config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeBaseConfig(entryModel: EntryModel, section: string) {
    const options = await getOptions();

    const modulePaths = [
        "node_modules",
        ...entryModel.addonDirs.map(dir => path.resolve(dir, "node_modules")),
        path.join(VANILLA_ROOT, "node_modules"),
    ];

    const aliases = Object.keys(entryModel.aliases).join(", ");
    const message = `Building section ${chalk.yellowBright(section)} with the following aliases
${chalk.green(aliases)}`;
    printVerbose(message);

    const babelPlugins: string[] = [];
    const hotLoaders: any[] = [];
    const hotAliases: any = {};
    if (options.mode === BuildMode.DEVELOPMENT) {
        babelPlugins.push(require.resolve("react-hot-loader/babel"));
        hotLoaders.push(require.resolve("react-hot-loader/webpack"));
        hotAliases["react-dom"] = require.resolve("@hot-loader/react-dom");
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
                        const exlusionRegex = new RegExp(`node_modules/(${modulesRequiringTranspilation.join("|")})/`);

                        // We need to transpile quill's ES6 because we are building from source.
                        return /node_modules/.test(modulePath) && !exlusionRegex.test(modulePath);
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
                        [BuildMode.DEVELOPMENT, BuildMode.TEST, BuildMode.TEST_DEBUG, BuildMode.TEST_WATCH].includes(
                            options.mode,
                        )
                            ? "style-loader"
                            : MiniCssExtractPlugin.loader,
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
                                implementation: require("sass"), // Use dart sass
                            },
                        },
                    ],
                },
            ],
        },
        performance: { hints: false },
        plugins: [
            new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /en|es|fr|pt|ch/),
            new webpack.DefinePlugin({
                __BUILD__SECTION__: JSON.stringify(section),
            }),
        ] as any[],
        resolve: {
            modules: modulePaths,
            alias: {
                ...hotAliases,
                ...entryModel.aliases,
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

    // This is the only flag we are given by infrastructure to indicate we are in a lower memory environment.
    config.plugins.push(
        new WebpackBar({
            name: section,
        }),
    );

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
