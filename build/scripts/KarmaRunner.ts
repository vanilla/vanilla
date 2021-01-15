/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path from "path";
import { makeTestConfig } from "./configs/makeTestConfig";
import { VANILLA_ROOT, TEST_FILE_ROOTS, PACKAGES_TEST_ENTRY } from "./env";
import { IBuildOptions, BuildMode } from "./buildOptions";
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
        TEST_FILE_ROOTS.forEach((fileRoot) => {
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
            frameworks: ["mocha", "chai", "viewport"],
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
            middleware: ["genericImageMiddleware"],
            plugins: [
                "karma-chai",
                "karma-viewport",
                "karma-chrome-launcher",
                "karma-webpack",
                "karma-sourcemap-loader",
                "karma-mocha",
                "karma-mocha-reporter",
                "karma-spec-reporter",
                "karma-expect",
                { "middleware:genericImageMiddleware": ["value", genericImageMiddleware] },
            ],
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

function genericImageMiddleware(req: any, res: any, next: any) {
    const DUMMY_IMAGES = {
        png: {
            base64:
                "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
            type: "image/png",
        },
        jpg: {
            base64:
                "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==",
            type: "image/jpeg",
        },
        gif: {
            base64: "data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=",
            type: "image/gif",
        },
    };

    const imageExt = req.url.split(".").pop();
    const dummy = DUMMY_IMAGES[imageExt as keyof typeof DUMMY_IMAGES];

    if (dummy) {
        // Table of files to ignore
        const img = Buffer.from(dummy.base64, "base64");
        res.writeHead(200, {
            "Content-Type": dummy.type,
            "Content-Length": img.length,
        });
        return res.end(img);
    }
    next();
}
