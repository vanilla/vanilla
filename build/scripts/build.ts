/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import fs from "fs";
import { getOptions, BuildMode } from "./buildOptions";
import { spawnChildProcess } from "./utility/moduleUtils";
import Builder from "./Builder";
import path from "path";
import { DIST_DIRECTORY, VANILLA_ROOT } from "./env";

/**
 * Run the build. Options are passed as arguments from the command line.
 * @see https://docs.vanillaforums.com/developer/tools/building-frontend/
 */
void getOptions().then(async (options) => {
    const builder = new Builder(options);
    await builder.build();

    if (options.mode === BuildMode.PRODUCTION) {
        const exceptions = ["modern", "polyfills", "monaco", "."];
        const dirs = fs
            .readdirSync(DIST_DIRECTORY)
            .filter((dir) => exceptions.every((exp) => !dir.includes(exp)))
            .map((dir) => {
                return path.join(DIST_DIRECTORY, dir, "**/*.js");
            });
        dirs.push(path.join(VANILLA_ROOT, "js/**/*.js"));

        await spawnChildProcess("yarn", ["es-check", "es7", ...dirs], {
            stdio: "inherit",
        }).catch((e) => {
            process.exit(1);
        });
    }
});
