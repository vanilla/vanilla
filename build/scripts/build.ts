/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import path from "path";
import del from "del";
import webpack, { Stats } from "webpack";
import { makeProdConfig } from "./configs/makeProdConfig";
import { makeDevConfig } from "./configs/makeDevConfig";
import serve, { InitializedKoa, Options } from "webpack-serve";
import { getOptions, BuildMode } from "./options";
import chalk from "chalk";
import { installNodeModules } from "./utility/moduleUtils";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";
import { printError, print, fail } from "./utility/utils";
import { DIST_DIRECTORY } from "./env";

void Promise.all([installNodeModules("forum"), installNodeModules("admin"), installNodeModules("knowledge")]).then(run);

/**
 * Run the requested build type.
 */
async function run() {
    const options = await getOptions();
    switch (options.mode) {
        case BuildMode.PRODUCTION:
        case BuildMode.ANALYZE:
            return await runProd();
        case BuildMode.DEVELOPMENT:
            return await runDev();
        case BuildMode.POLYFILLS:
            return await runPolyfill();
    }
}

const statOptions = {
    chunks: false, // Makes the build much quieter
    modules: false,
    entrypoints: false,
    warnings: false,
    colors: true, // Shows colors in the console
};

async function runProd() {
    // Cleanup
    del.sync(path.join(DIST_DIRECTORY, "**"));
    const config = [await makeProdConfig("forum"), await makeProdConfig("admin"), await makeProdConfig("knowledge")];
    const compiler = webpack(config);
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            printError("The build encountered an error:" + err);
        }

        print(stats.toString(statOptions));
    });
}

async function runPolyfill() {
    const config = await makePolyfillConfig();
    const compiler = webpack(config);
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            printError("The build encountered an error:" + err);
        }

        print(stats.toString(statOptions));
    });
}

async function runDev() {
    const buildOptions = await getOptions();
    const hotReloadConfigSet = buildOptions.phpConfig.HotReload && buildOptions.phpConfig.HotReload.Enabled;
    if (buildOptions.mode === BuildMode.DEVELOPMENT && !hotReloadConfigSet) {
        const message = chalk.red(`
You've enabled a development build without enabling hot reload. Add the following to your config.
${chalk.yellowBright("$Configuration['HotReload']['Enabled'] = false;")}`);
        fail(message);
    }

    const config = [await makeDevConfig("forum"), await makeDevConfig("admin"), await makeDevConfig("knowledge")];
    const compiler = webpack(config) as any;
    const argv = {};
    const enhancer = (app: InitializedKoa) => {
        app.use(async (context, next) => {
            context.set("Access-Control-Allow-Origin", "*");
            context.set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
            context.set("Access-Control-Allow-Methods", "POST, GET, PUT, DELETE, OPTIONS");
            await next();
        });
    };

    const options: Options = {
        compiler,
        port: 3030,
        add: enhancer,
        clipboard: false,
        devMiddleware: {
            publicPath: "http://localhost:3030/",
            stats: statOptions,
        },
    };

    await serve(argv, options);
}
