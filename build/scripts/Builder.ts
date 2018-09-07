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
import { getOptions, BuildMode, IBuildOptions } from "./options";
import chalk from "chalk";
import { installNodeModulesInDir } from "./utility/moduleUtils";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";
import { printError, print, fail } from "./utility/utils";
import { DIST_DIRECTORY } from "./env";
import EntryModel from "./utility/EntryModel";

export default class Builder {
    private statOptions = {
        chunks: false, // Makes the build much quieter
        modules: false,
        entrypoints: false,
        warnings: false,
        colors: true, // Shows colors in the console
    };

    private entryModel: EntryModel;

    constructor(private options: IBuildOptions) {
        this.entryModel = new EntryModel(options);
    }

    public async build() {
        await this.entryModel.init();
        await this.installNodeModules();
        switch (this.options.mode) {
            case BuildMode.PRODUCTION:
            case BuildMode.ANALYZE:
                return await this.runProd();
            case BuildMode.DEVELOPMENT:
                return await this.runDev();
            case BuildMode.POLYFILLS:
                return await this.runPolyfill();
        }
    }

    private async installNodeModules() {
        return await Promise.all(this.entryModel.addonDirs.map(installNodeModulesInDir));
    }

    private async runProd() {
        // Cleanup
        del.sync(path.join(DIST_DIRECTORY, "**"));
        const sections = await this.entryModel.getSections();
        const config = await Promise.all(sections.map(section => makeProdConfig(this.entryModel, section)));
        const compiler = webpack(config);
        compiler.run((err: Error, stats: Stats) => {
            if (err || stats.hasErrors()) {
                print(stats.toString(this.statOptions));
                fail(`\nThe build encountered an error: ${err}`);
            }

            print(stats.toString(this.statOptions));
        });
    }
    private async runPolyfill() {
        const config = await makePolyfillConfig(this.entryModel);
        const compiler = webpack(config);
        compiler.run((err: Error, stats: Stats) => {
            if (err) {
                printError("The build encountered an error:" + err);
            }

            print(stats.toString(this.statOptions));
        });
    }

    private async runDev() {
        const buildOptions = await getOptions();
        const hotReloadConfigSet = buildOptions.phpConfig.HotReload && buildOptions.phpConfig.HotReload.Enabled;
        if (buildOptions.mode === BuildMode.DEVELOPMENT && !hotReloadConfigSet) {
            const message = chalk.red(`
You've enabled a development build without enabling hot reload. Add the following to your config.
${chalk.yellowBright("$Configuration['HotReload']['Enabled'] = false;")}`);
            fail(message);
        }

        const sections = await this.entryModel.getSections();
        const config = await Promise.all(sections.map(section => makeDevConfig(this.entryModel, section)));
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
                stats: this.statOptions,
            },
        };

        await serve(argv, options);
    }
}
