import * as path from "path";
import webpack, { Configuration, Module } from "webpack";
import { VANILLA_ROOT } from "./vanillaPaths";
import { getAddonAliasMapping, getScriptSourceFiles, getForumEntries } from "./utils";
import { makeBaseConfig } from "./makeBaseConfig";

export async function makeProdConfig() {
    const baseConfig: Configuration = (await makeBaseConfig()) as any;
    const forumEntries = await getForumEntries();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    baseConfig.output = {
        filename: "[name].min.js",
        path: VANILLA_ROOT,
    };
    baseConfig.optimization = {
        runtimeChunk: {
            name: "js/webpack/runtime",
        },
        splitChunks: {
            cacheGroups: {
                commons: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "vendors",
                    chunks: "all",
                },
            },
        },
    };
    return baseConfig;
}
