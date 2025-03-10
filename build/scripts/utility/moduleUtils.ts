/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { spawn } from "child_process";
import fse from "fs-extra";
import path from "path";
import { DIST_DIRECTORY, VANILLA_ROOT } from "../env";
import { print } from "./utils";

/**
 * Copy files from the monaco editor the dist directory.
 */
export function copyMonacoEditorModule() {
    fse.ensureDir(DIST_DIRECTORY);
    const MONACO_PATH = path.join(VANILLA_ROOT, "node_modules", "monaco-editor");

    print("Copying monaco editor to /dist");
    if (fse.existsSync(MONACO_PATH)) {
        fse.copySync(MONACO_PATH, path.resolve(DIST_DIRECTORY, "monaco-editor-52-0"), {
            filter: (file) => {
                if (file.match(/\/monaco-editor\/node_modules/) || file.match(/\/monaco-editor\/(dev|esm|min-maps)/)) {
                    return false;
                } else {
                    return true;
                }
            },
        });
    }
}

/**
 * Spawn a child build process. Wraps child_process.spawn.
 *
 * @param command - The command to start.
 * @param args - Arguments for the command.
 * @param options - Options to pass to `child_process.spawn`.
 *
 * @returns Return if the process exits cleanly.
 */
export function spawnChildProcess(command: string, args: string[], options: any): Promise<boolean> {
    return new Promise((resolve, reject) => {
        const task = spawn(command, args, options);

        task.on("close", (code) => {
            if (code !== 0) {
                reject(new Error(`command "${command} exited with a non-zero status code."`));
            }
            return resolve(true);
        });

        task.on("error", (err) => {
            return reject(err);
        });
    });
}
