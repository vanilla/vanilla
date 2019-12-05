/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import webpack, { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import EntryModel from "../utility/EntryModel";
// tslint:disable
const TSDocgenPlugin = require("react-docgen-typescript-webpack-plugin");
const merge = require("webpack-merge");

/**
 * Create the storybook configuration.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeStoryConfig(baseStorybookConfig: Configuration, entryModel: EntryModel) {
    baseStorybookConfig.module!.rules.push({
        test: /\.story\.tsx?$/,
        loaders: [
            {
                loader: require.resolve("@storybook/addon-storysource/loader"),
                options: { parser: "typescript" },
            },
        ],
        enforce: "pre",
    });
    const baseConfig: Configuration = await makeBaseConfig(entryModel, "storybook");
    baseConfig.mode = "development";
    baseConfig.optimization = {
        splitChunks: false,
    };
    baseConfig.plugins?.push(
        new webpack.DefinePlugin({
            ["process.env.NODE_ENV"]: "'test'",
        }),
    );
    return merge(baseStorybookConfig, baseConfig as any);
}
