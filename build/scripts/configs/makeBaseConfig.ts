/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import chalk from "chalk";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import * as path from "path";
import webpack from "webpack";
import WebpackBar from "webpackbar";
import { BuildMode, getOptions } from "../buildOptions";
import { DIST_NAME, VANILLA_ROOT } from "../env";
import EntryModel from "../utility/EntryModel";
import { printVerbose } from "../utility/utils";
import { svgLoader } from "./svgLoader";
import globby from "globby";
import { notEmpty } from "@vanilla/utils";
const CircularDependencyPlugin = require("circular-dependency-plugin");

/**
 * Create the core webpack config.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeBaseConfig(entryModel: EntryModel, section: string) {
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

    //this will slightly slow down the build because of swc-loader parseMap, but we'll have the class prefixes
    const emotionAutoLabel = options.generateClassNames && BuildMode.DEVELOPMENT === options.mode;

    const config: any = {
        context: VANILLA_ROOT,
        parallelism: 50, // Intentionally brought down from 50 to reduce memory usage.
        cache: options.mode === BuildMode.DEVELOPMENT,
        module: {
            rules: [
                {
                    test: /\.(m?jsx?|tsx?)$/,
                    exclude: (modulePath: string) => {
                        const modulesRequiringTranspilation = [
                            "@vanilla/.*",
                            "@monaco-editor/react.*",
                            "ajv.*",
                            "@?react-spring.*",
                            "highlight.js",
                            "@simonwep.*",
                            "serialize-error", // Comes from swagger-ui
                            // These are needed for plate
                            "@udecode/.*",
                            "jotai",
                            "zustand",
                            "@radix-ui/.*",
                            "@floating-ui/.*",
                            "downshift",
                        ];
                        const exclusionRegex = new RegExp(`node_modules/(${modulesRequiringTranspilation.join("|")})/`);

                        if (modulePath.includes("core-js")) {
                            return true;
                        }

                        // We need to transpile quill's ES6 because we are building from source.
                        return /node_modules/.test(modulePath) && !exclusionRegex.test(modulePath);
                    },
                    use: [
                        (BuildMode.PRODUCTION === options.mode || emotionAutoLabel) && section !== "storybook"
                            ? {
                                  loader: "babel-loader",
                                  options: {
                                      // Only load the emotion transform.
                                      babelrc: false,
                                      configFile: false,
                                      babelrcRoots: [],
                                      presets: [],
                                      plugins: [
                                          [
                                              "@emotion",
                                              {
                                                  autoLabel: "always",
                                                  labelFormat: "[filename]-[local]",
                                              },
                                          ],
                                      ],
                                  },
                              }
                            : null,
                        {
                            loader: "swc-loader",
                            options: {
                                parseMap: emotionAutoLabel,
                                jsc: {
                                    transform: {
                                        react: {
                                            // Enable react refresh in development.
                                            refresh: options.mode === BuildMode.DEVELOPMENT,
                                        },
                                    },
                                },
                            },
                        },
                    ].filter(notEmpty),
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
            new webpack.DefinePlugin({
                __DIST__NAME__: JSON.stringify(DIST_NAME),
                __BUILD__SECTION__: JSON.stringify(section),
                "process.env.IS_WEBPACK": true,
            }),
            new webpack.IgnorePlugin({
                resourceRegExp: /^\.\/locale$/,
                contextRegExp: /moment$/,
            }),
        ] as any[],
        resolve: {
            modules: modulePaths,
            mainFields: ["browser", "module", "main"],
            alias: {
                "@dashboard/compatibilityStyles/Leaderboard.variables": path.resolve(
                    VANILLA_ROOT,
                    "library/src/scripts/leaderboardWidget/LeaderboardWidget.variables.ts",
                ),
                "@dashboard/compatibilityStyles/Leaderboard.styles": path.resolve(
                    VANILLA_ROOT,
                    "library/src/scripts/leaderboardWidget/LeaderboardWidget.styles.ts",
                ),
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
                filename: "[contenthash].min.css",
                chunkFilename: "async/[contenthash].min.css",
                insert: (linkTag) => {
                    // Make sure the static stylesheets (even async ones), get inserted before emotion styles.
                    // Emotion styles are newer and should be more specific.
                    const emotionCSSLink = document.head.querySelector("[data-emotion='css']");
                    if (emotionCSSLink) {
                        document.head.insertBefore(linkTag, emotionCSSLink);
                    } else {
                        document.head.appendChild(linkTag);
                    }
                },
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
