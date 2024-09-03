/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globbySync } from "globby";
import path from "path";
import { VANILLA_ROOT } from "./scripts/env";
import * as sass from "sass";
import fse from "fs-extra";
import { print, printSection } from "./scripts/utility/utils";

run();

function run() {
    printSection("Building Legacy Dashboard");
    const DASHBOARD_ROOT = path.resolve(VANILLA_ROOT, "applications/dashboard");
    const glob = path.join(DASHBOARD_ROOT, "/scss/**/*.scss");
    print(`Searching ${glob}.`);

    const scssFiles = globbySync(glob);
    print(`Found ${scssFiles.length} Sass files.`);
    const entries: string[] = [];

    for (const scssFile of scssFiles) {
        const name = scssFile.split("/").slice(-1)[0];
        if (name[0] !== "_" && !scssFile.includes("/vendors/")) {
            entries.push(scssFile);
        }
    }
    print(`Found ${entries.length} Sass entrypoints.`);

    const sassCompiler = sass.initCompiler();
    for (const entry of entries) {
        print("Compiled Sass file: " + entry);
        const outputPath = entry.replace("/scss/", "/design/").replace(".scss", ".css");

        const output = sass.renderSync({ file: entry, outFile: outputPath, sourceMap: true, sourceMapContents: true });
        fse.writeFileSync(outputPath, output.css);
        fse.writeFileSync(outputPath + ".map", output.map!);
    }
}
