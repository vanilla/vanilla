/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import chalk from "chalk";
import fse from "fs-extra";
import path from "path";
import webpack, { Configuration, Stats } from "webpack";
import WebpackDevServer, { Configuration as DevServerConfiguration } from "webpack-dev-server";
import { makeDevConfig } from "./configs/makeDevConfig";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";
import { makeProdConfig } from "./configs/makeProdConfig";
import { DIST_DIRECTORY, VANILLA_ROOT } from "./env";
import { BuildMode, getOptions, IBuildOptions } from "./buildOptions";
import EntryModel from "./utility/EntryModel";
import { copyMonacoEditorModule, installYarn } from "./utility/moduleUtils";
import { fail, print, printSection } from "./utility/utils";

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
        await installYarn();
    }

    /**
     * Run the build based on the provided options.
     */
    public async build() {
        if (this.options.cleanCache) {
            await fse.emptyDir(path.join(VANILLA_ROOT, "node_modules/.cache"));
        }

        await this.entryModel.init();
        await installYarn();
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
        await fse.emptyDir(path.join(DIST_DIRECTORY));
        copyMonacoEditorModule();
        const sections = await this.entryModel.getSections();
        let configs: webpack.Configuration[];
        if (this.options.modern) {
            configs = await Promise.all([
                ...sections.map((section) => makeProdConfig(this.entryModel, section, false)),
                ...sections.map((section) => makeProdConfig(this.entryModel, section, true)),
                makePolyfillConfig(this.entryModel),
            ]);
        } else {
            configs = await Promise.all([
                ...sections.map((section) => makeProdConfig(this.entryModel, section, true)),
                makePolyfillConfig(this.entryModel),
            ]);
        }

        // Running the builds individually is actually faster since webpack 5
        // We can parellize many function per build and saturate the CPU.
        // This also lets you see individual sections errors faster.
        for (const config of configs) {
            await this.runBuild(config);
        }
    }

    /**
     * Build a single webpack config.
     *
     * @param config The config to build.
     */
    private async runBuild(config: Configuration | Configuration[]) {
        return new Promise<void>((resolve) => {
            const compiler = webpack(config as Configuration);
            compiler.run((err: Error, stats: Stats) => {
                if (err || stats.hasErrors()) {
                    print(stats.toString(this.statOptions));
                    fail(`\nThe build encountered an error: ${err}`);
                }

                print(stats.toString(this.statOptions));
                compiler.close(() => {
                    resolve();
                });
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
        copyMonacoEditorModule();
        const buildOptions = await getOptions();
        const hotReloadConfigSet = buildOptions.phpConfig.HotReload && buildOptions.phpConfig.HotReload.Enabled;
        if (buildOptions.mode === BuildMode.DEVELOPMENT && !hotReloadConfigSet) {
            const message = chalk.red(`
You've enabled a development build without enabling hot reload. Add the following to your config.
${chalk.yellowBright("$Configuration['HotReload']['Enabled'] = true;")}`);
            fail(message);
        }

        const certPath = path.resolve(VANILLA_ROOT, "../vanilla-docker/resources/certificates");
        const crtFile = path.resolve(certPath, "wildcard.vanilla.localhost.crt");
        const keyFile = path.resolve(certPath, "wildcard.vanilla.localhost.key");

        const https = fse.existsSync(certPath)
            ? {
                  key: fse.readFileSync(keyFile),
                  cert: fse.readFileSync(crtFile),
              }
            : false;

        if (https) {
            print(chalk.green("Found SSL certs. Serving over https://"));
        }

        const devServerOptions: DevServerConfiguration = {
            host: "webpack.vanilla.localhost",
            port: 3030,
            hotOnly: true,
            open: false,
            https,
            disableHostCheck: true,
            sockHost: "webpack.vanilla.localhost:3030",
            public: "webpack.vanilla.localhost:3030",
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
                "Access-Control-Allow-Methods": "POST, GET, PUT, DELETE, OPTIONS",
            },
            publicPath: `https://webpack.vanilla.localhost:3030/`,
            stats: this.statOptions,
        };

        const sections = await this.entryModel.getSections();
        const config = await Promise.all(
            sections.map(async (section) => {
                const sectionConfig = await makeDevConfig(this.entryModel, section);
                WebpackDevServer.addDevServerEntrypoints(sectionConfig as any, devServerOptions);
                return sectionConfig;
            }),
        );
        const compiler = webpack(config) as any;

        const server = new WebpackDevServer(compiler, devServerOptions);
        server.listen(3030, "127.0.0.1");
    }
}
