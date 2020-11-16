/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getOptions, BuildMode } from "./buildOptions";
import { spawnChildProcess } from "./utility/moduleUtils";
import Builder from "./Builder";
import path from "path";
import { DIST_DIRECTORY } from "./env";

/**
 * Run the build. Options are passed as arguments from the command line.
 * @see https://docs.vanillaforums.com/developer/tools/building-frontend/
 */
void getOptions().then(async options => {
    const builder = new Builder(options);
    await builder.build();

    if (options.mode === BuildMode.PRODUCTION) {
        const filesToCheck = [
            path.join(DIST_DIRECTORY, "polyfills.min.js"),
            path.join(DIST_DIRECTORY, "forum/vendors.min.js"),
            path.join(DIST_DIRECTORY, "forum/shared.min.js"),
            path.join(DIST_DIRECTORY, "admin/vendors.min.js"),
            path.join(DIST_DIRECTORY, "admin/shared.min.js"),
        ];

        await spawnChildProcess("npx", ["es-check", "es5", ...filesToCheck], { stdio: "inherit" }).catch(e => {
            process.exit(1);
        });
    }
});
