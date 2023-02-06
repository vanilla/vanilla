/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import chalk from "chalk";
import fse from "fs-extra";
import path from "path";
import webpack, { Configuration, Stats } from "webpack";
import WebpackDevServer, { Configuration as DevServerConfiguration, ServerConfiguration } from "webpack-dev-server";
import { BuildMode, getOptions, IBuildOptions } from "./buildOptions";
import { makeDevConfig } from "./configs/makeDevConfig";
import { makeEmbedConfig } from "./configs/makeEmbedConfig";
import { makePolyfillConfig } from "./configs/makePolyfillConfig";
import { makeProdConfig } from "./configs/makeProdConfig";
import { DIST_ROOT_DIRECTORY, VANILLA_ROOT } from "./env";
import EntryModel from "./utility/EntryModel";
import { copyMonacoEditorModule, installYarn } from "./utility/moduleUtils";
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
    private statOptions: any;

    private entryModel: EntryModel;

    /**
     * @param options The options to build with.
     */
    constructor(private options: IBuildOptions) {
        this.entryModel = new EntryModel(options);
        this.statOptions = this.options.verbose ? "normal" : "minimal";
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
     * Run the production build. This fails aggressively if there are any errors.
     * It is also much slower than the development build.
     */
    private async runProd() {
        // Cleanup
        await fse.emptyDir(path.join(DIST_ROOT_DIRECTORY));
        copyMonacoEditorModule();
        const sections = await this.entryModel.getSections();
        let configs: webpack.Configuration[];
        configs = await Promise.all([
            ...sections.map((section) => makeProdConfig(this.entryModel, section)),
            makePolyfillConfig(this.entryModel),
        ]);

        configs.push(makeEmbedConfig(true));

        // Running the builds individually is actually faster since webpack 5
        // We can parellize many function per build and saturate the CPU.
        // This also lets you see individual sections errors faster.
        let currentConfig = configs.shift();
        while (currentConfig) {
            await this.runBuild(currentConfig);
            currentConfig = configs.shift();
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
                if (stats?.hasErrors()) {
                    print(stats.toString(this.statOptions));
                }
                if (err) {
                    fail(`\nThe build encountered an error: ${err}`);
                }

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
        const hasSslCerts = fse.existsSync(certPath);

        const serverOptions: ServerConfiguration = {};
        if (hasSslCerts) {
            print(chalk.green("Found SSL certs. Serving over https://"));
            serverOptions.type = "spdy";
            serverOptions.options = {
                key: fse.readFileSync(keyFile),
                cert: fse.readFileSync(crtFile),
            };
        }

        const devServerOptions: DevServerConfiguration = {
            host: "webpack.vanilla.localhost",
            port: 3030,
            hot: "only",
            open: false,
            static: false,
            setupExitSignals: false,
            server: serverOptions,
            allowedHosts: ["vanilla.localhost", ".vanilla.localhost", "localhost", "127.0.0.1"],
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
                "Access-Control-Allow-Methods": "POST, GET, PUT, DELETE, OPTIONS",
            },
        };

        const sections = await this.entryModel.getSections();
        const config = await Promise.all(
            sections.map(async (section) => {
                const sectionConfig = await makeDevConfig(this.entryModel, section);
                return sectionConfig;
            }),
        );
        config.push(makeEmbedConfig(false));
        const compiler = webpack(config);

        const server = new WebpackDevServer(devServerOptions, compiler as any);
        server.start();
    }
}
