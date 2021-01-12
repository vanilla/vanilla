/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import webpack, { Configuration } from "webpack";
import { makeBaseConfig } from "./makeBaseConfig";
import EntryModel from "../utility/EntryModel";

/**
 * Create the storybook configuration.
 *
 * @param section - The section of the app to build. Eg. forum | admin | knowledge.
 */
export async function makeStoryConfig(baseStorybookConfig: Configuration, entryModel: EntryModel) {
    const baseConfig = await makeBaseConfig(entryModel, "storybook");

    // Apply our module resolutions.
    baseStorybookConfig.resolve = baseConfig.resolve;

    // Ensure our environmental variable is applied.
    baseStorybookConfig.plugins?.push(
        new webpack.DefinePlugin({
            ["process.env.NODE_ENV"]: "'test'",
        }),
    );

    // We need to process SCSS files.
    baseStorybookConfig.module?.rules.push({
        test: /\.scss$/,
        use: [
            {
                loader: "style-loader",
                options: {
                    injectType: "singletonStyleTag",
                    insert: function insertAtTop(element: HTMLElement) {
                        const parent = document.head;
                        parent.prepend(element);
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
                loader: "sass-loader",
                options: {
                    implementation: require("sass"), // Use dart sass
                },
            },
        ],
    });

    baseStorybookConfig.module?.rules.unshift({
        test: /\/(design|resources)\/.*\.(css|ttf)$/,
        loader: "file-loader",
        options: {
            name: "[path][name]-[hash].[ext]",
        },
    });

    baseStorybookConfig.module?.rules.push({
        test: /\.html$/,
        use: "raw-loader",
    });

    return baseStorybookConfig;
}
