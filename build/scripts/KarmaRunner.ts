/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path from "path";
import { makeTestConfig } from "./configs/makeTestConfig";
import { VANILLA_ROOT, TEST_FILE_ROOTS, PACKAGES_TEST_ENTRY } from "./env";
import { IBuildOptions, BuildMode } from "./options";
import EntryModel from "./utility/EntryModel";
// tslint:disable-next-line
const Karma = require("karma");

const TS_PROCESSORS = ["webpack", "sourcemap"];

export class KarmaRunner {
    private files: string[] = [PACKAGES_TEST_ENTRY];
    private preprocessors: {
        [key: string]: string[];
    } = {
        [PACKAGES_TEST_ENTRY]: TS_PROCESSORS,
    };
    private entryModel: EntryModel;

    public constructor(private options: IBuildOptions) {
        this.entryModel = new EntryModel(options);
        this.initFileDirs();
    }

    public async run() {
        void (await this.entryModel.init());
        const config = await this.makeKarmaConfig();
        const server = new Karma.Server(config, (exitCode: number) => {
            process.exit(exitCode);
        });
        server.start();
    }

    private initFileDirs = () => {
        TEST_FILE_ROOTS.forEach(fileRoot => {
            const { normalize, join } = path;
            const tsPath = normalize(join(fileRoot, "src/**/*.test.ts"));
            const tsxPath = normalize(join(fileRoot, "src/**/*.test.tsx"));
            const setupPath = normalize(join(fileRoot, "src/**/__tests__/setup.ts"));

            this.files.push(setupPath);
            this.preprocessors[tsPath] = TS_PROCESSORS;
            this.preprocessors[tsxPath] = TS_PROCESSORS;
            this.preprocessors[setupPath] = TS_PROCESSORS;
        });
    };

    private async makeKarmaConfig(): Promise<any> {
        return {
            preprocessors: this.preprocessors,
            files: this.files,
            // base path, that will be used to resolve files and exclude
            basePath: VANILLA_ROOT,
            frameworks: ["mocha", "chai"],
            reporters: ["mocha"],
            // reporter options
            mochaReporter: {
                output: "minimal",
                showDiff: true,
            },
            logLevel: Karma.constants.LOG_INFO,
            port: 9876, // karma web server port
            colors: true,
            mime: {
                "text/x-typescript": ["ts"],
            },
            browsers: [this.options.mode === BuildMode.TEST_DEBUG ? "ChromeDebug" : "ChromeHeadlessNoSandbox"],
            autoWatch: true,
            webpackMiddleware: {
                // webpack-dev-middleware configuration
                // i. e.
                stats: "errors-only",
            },
            webpack: await makeTestConfig(this.entryModel),
            singleRun: this.options.mode === BuildMode.TEST, // All other tests modes are in "watch mode".
            concurrency: Infinity,
            // you can define custom flags
            customLaunchers: {
                ChromeHeadlessNoSandbox: {
                    base: "ChromeHeadless",
                    flags: ["--no-sandbox"],
                },
                ChromeDebug: {
                    base: "Chrome",
                    flags: ["--remote-debugging-port=9333"],
                },
            },
        };
    }
}
