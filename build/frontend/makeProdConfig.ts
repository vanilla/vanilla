import * as path from "path";
import webpack, { Configuration, Module } from "webpack";
import { VANILLA_ROOT } from "./vanillaPaths";
import { getAddonAliasMapping, getScriptSourceFiles, getForumEntries, getOptions, BuildMode } from "./utils";
import { makeBaseConfig } from "./makeBaseConfig";
import { BundleAnalyzerPlugin } from "webpack-bundle-analyzer";

export async function makeProdConfig() {
    const baseConfig: Configuration = (await makeBaseConfig()) as any;
    const forumEntries = await getForumEntries();

    baseConfig.mode = "production";
    baseConfig.entry = forumEntries;
    baseConfig.output = {
        filename: "[name].min.js",
        chunkFilename: "[name].min.js",
        path: VANILLA_ROOT,
    };
    baseConfig.optimization = {
        runtimeChunk: {
            name: "js/webpack/runtime",
        },
        splitChunks: {
            chunks: "initial",
            cacheGroups: {
                commons: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "js/webpack/vendors",
                    chunks: "initial",
                },
                library: {
                    test: /[\\/]applications[\\/]dashboard[\\/]src[\\/]scripts[\\/]/,
                    name: "applications/dashboard/js/webpack/library",
                    chunks: "all",
                    minChunks: 2,
                },
            },
        },
    };

    if (getOptions().mode === BuildMode.ANALYZE) {
        baseConfig.plugins!.push(new BundleAnalyzerPlugin());
    }

    return baseConfig;
}
