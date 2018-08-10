/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import webpack, { Stats } from "webpack";
import { makeProdConfig } from "./makeProdConfig";
import { makeDevConfig } from "./makeDevConfig";
import serve, { Result, InitializedKoa } from "webpack-serve";
import { getOptions, BuildMode } from "./utils";

void run();

async function run() {
    switch (getOptions().mode) {
        case BuildMode.PRODUCTION:
            return await runProd();
        case BuildMode.DEVELOPMENT:
            return await runDev();
    }
}

async function runProd() {
    const config = [await makeProdConfig("forum"), await makeProdConfig("admin")];
    const compiler = webpack(config);
    const logger = console;
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            logger.error("The build encountered an error:" + err);
        }

        logger.log(
            stats.toString({
                chunks: false, // Makes the build much quieter
                modules: false,
                entrypoints: false,
                warnings: false,
                colors: true, // Shows colors in the console
            }),
        );
    });
}

async function runDev() {
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

    serve(argv, { compiler, port: 3030, add: enhancer, devMiddleware: { publicPath: "http://localhost:3030/" } }).then(
        (result: Result) => {
            console.log("Started dev server");
        },
    );
}
