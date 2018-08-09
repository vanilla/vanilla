/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import * as path from "path";
import { VANILLA_ROOT } from "./vanillaPaths";

export function makeBaseConfig(addonPaths: string[]) {
    const resolvePaths = ["node_modules", ...addonPaths.map(dir => path.resolve(dir, "node_modules"))];

    return {
        context: VANILLA_ROOT,
        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    exclude: ["node_modules"],
                    include: [
                        /\/src\/scripts/,
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
                    include: [/\/src\/scripts/, /tests\/javascript/],
                    use: [
                        {
                            loader: "awesome-typescript-loader",
                            options: {
                                useBabel: true,
                                useCache: true,
                                configFileName: path.resolve(VANILLA_ROOT, "tsconfig.json"),
                                forceIsolatedModules: true,
                                babelOptions: {
                                    babelrc: false,
                                    presets: ["@vanillaforums/babel-preset"],
                                },
                            },
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
        resolve: {
            modules: [
                path.join(VANILLA_ROOT, "node_modules"),
                path.join(VANILLA_ROOT, "plugins/rich-editor/node_modules"),
                path.join(VANILLA_ROOT, "tests/node_modules"),
            ],
            alias: {
                "@dashboard": path.resolve(VANILLA_ROOT, "applications/dashboard/src/scripts/"),
                "@vanilla": path.resolve(VANILLA_ROOT, "applications/vanilla/src/scripts/"),
                "@rich-editor": path.resolve(VANILLA_ROOT, "plugins/rich-editor/src/scripts/"),
                "@testroot": path.resolve(VANILLA_ROOT, "tests/javascript/"),
            },
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

module.exports = () => {};
