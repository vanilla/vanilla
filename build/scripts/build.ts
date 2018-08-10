/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import webpack, { Stats } from "webpack";
import { makeProdConfig } from "./configs/makeProdConfig";
import { makeDevConfig } from "./configs/makeDevConfig";
import serve, { Result, InitializedKoa, Options } from "webpack-serve";
import { getOptions, BuildMode } from "./options";
import chalk from "chalk";
import { installNodeModules } from "./utility/moduleUtils";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";

void Promise.all([installNodeModules("forum"), installNodeModules("admin")]).then(run);

/**
 * Run the requested build type.
 */
async function run() {
    const options = await getOptions();
    switch (options.mode) {
        case BuildMode.PRODUCTION:
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
    const config = [await makeProdConfig("forum"), await makeProdConfig("admin")];
    const compiler = webpack(config);
    const logger = console;
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            logger.error("The build encountered an error:" + err);
        }

        logger.log(stats.toString(statOptions));
    });
}

async function runPolyfill() {
    const config = await makePolyfillConfig();
    const compiler = webpack(config);
    const logger = console;
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            logger.error("The build encountered an error:" + err);
        }

        logger.log(stats.toString(statOptions));
    });
}

async function runDev() {
    const buildOptions = await getOptions();
    const hotReloadConfigSet = buildOptions.phpConfig.HotReload && buildOptions.phpConfig.HotReload.Enabled;
    if (buildOptions.mode === BuildMode.DEVELOPMENT && !hotReloadConfigSet) {
        const message = chalk.red(`
You've enabled a development build without enabling hot reload. Add the following to your config.
${chalk.yellowBright("$Configuration['HotReload']['Enabled'] = false;")}`);
        // tslint:disable-next-line
        console.error(message);
        process.exit(1);
    }

    const config = [await makeDevConfig("forum"), await makeDevConfig("admin")];
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

    void serve(argv, options);
}
