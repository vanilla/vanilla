/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import chalk from "chalk";
import * as del from "del";
import path from "path";
import webpack, { Configuration, Stats } from "webpack";
import WebpackDevServer, { Configuration as DevServerConfiguration } from "webpack-dev-server";
import { makeDevConfig } from "./configs/makeDevConfig";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";
import { makeProdConfig } from "./configs/makeProdConfig";
import { DIST_DIRECTORY } from "./env";
import { BuildMode, getOptions, IBuildOptions } from "./options";
import EntryModel from "./utility/EntryModel";
import { installLerna } from "./utility/moduleUtils";
import { fail, print } from "./utility/utils";

/**
 * A class to build frontend assets.
 *
 * This supports
 * - A production build. (BuildMode.PRODUCTION)
 * - A development build. (BuildMode.DEVELOPMENT)
 * - A production build that spawns a bundle size analyzer (BuildMode.ANALYZE)
 * - A production build that only builds polyfills. (BuildMode.POLYFILLS)
 */
export default class Builder {
    private statOptions: any = this.options.verbose ? "normal" : "minimal";

    private entryModel: EntryModel;

    /**
     * @param options The options to build with.
     */
    constructor(private options: IBuildOptions) {
        this.entryModel = new EntryModel(options);
    }

    /**
     * Run just the install step of the build.
     */
    public async installOnly() {
        await this.entryModel.init();
        await installLerna();
    }

    /**
     * Run the build based on the provided options.
     */
    public async build() {
        await this.entryModel.init();
        await installLerna();
        switch (this.options.mode) {
            case BuildMode.PRODUCTION:
            case BuildMode.ANALYZE:
                return await this.runProd();
            case BuildMode.DEVELOPMENT:
                return await this.runDev();
        }
    }

    /**
     * Run the production build. This fails agressively if there are any errors.
     * It is also much slower than the development build.
     */
    private async runProd() {
        // Cleanup
        del.sync(path.join(DIST_DIRECTORY, "**"));
        const sections = await this.entryModel.getSections();
        const configs = await Promise.all([
            ...sections.map(section => makeProdConfig(this.entryModel, section)),
            makePolyfillConfig(this.entryModel),
        ]);

        if (this.options.lowMemory) {
            // In low memory environments we build sequentially instead of in parallel.
            for (const config of configs) {
                await this.runBuild(config);
            }
        } else {
            // Otherwise we build all configs at once.
            await this.runBuild(configs);
        }
    }

    /**
     * Build a single webpack config.
     *
     * @param config The config to build.
     */
    private async runBuild(config: Configuration | Configuration[]) {
        return new Promise(resolve => {
            const compiler = webpack(config as Configuration);
            compiler.run((err: Error, stats: Stats) => {
                if (err || stats.hasErrors()) {
                    print(stats.toString(this.statOptions));
                    fail(`\nThe build encountered an error: ${err}`);
                }

                print(stats.toString(this.statOptions));
                resolve();
            });
        });
    }

    /**
     * Run the development builds.
     *
     * Builds all enabled addons at once and serves them through an in memory development server.
     * Does not output any files to the disk and has VERY fast incremental builds.
     *
     * Requires a vanilla config to lookup configuration options.
     * Requires the HotReload config option to be enabled.
     */
    private async runDev() {
        const buildOptions = await getOptions();
        const hotReloadConfigSet = buildOptions.phpConfig.HotReload && buildOptions.phpConfig.HotReload.Enabled;
        if (buildOptions.mode === BuildMode.DEVELOPMENT && !hotReloadConfigSet) {
            const message = chalk.red(`
You've enabled a development build without enabling hot reload. Add the following to your config.
${chalk.yellowBright("$Configuration['HotReload']['Enabled'] = true;")}`);
            fail(message);
        }

        const devServerOptions: DevServerConfiguration = {
            host: this.options.devIp,
            port: 3030,
            open: false,
            https: false,
            disableHostCheck: true,
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
                "Access-Control-Allow-Methods": "POST, GET, PUT, DELETE, OPTIONS",
            },
            publicPath: `http://${this.options.devIp}:3030/`,
            stats: this.statOptions,
        };

        const sections = await this.entryModel.getSections();
        const config = await Promise.all(
            sections.map(async section => {
                const sectionConfig = await makeDevConfig(this.entryModel, section);
                WebpackDevServer.addDevServerEntrypoints(sectionConfig as any, devServerOptions);
                return sectionConfig;
            }),
        );
        const compiler = webpack(config) as any;

        const server = new WebpackDevServer(compiler, devServerOptions);
        server.listen(3030, devServerOptions.host || "127.0.0.1");
    }
}
