/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getOptions } from "../buildOptions";
import { print, fail } from "./utils";
import { spawn } from "child_process";

/**
 * Install dependancies for all requirements.
 *
 * @param options
 */
export async function installLerna() {
    const options = await getOptions();

    try {
        print(`Installing node_modules with lerna.`);
        const spawnOptions = options.verbose ? { stdio: "inherit" } : {};
        await spawnChildProcess("yarn", ["bootstrap"], spawnOptions);
    } catch (err) {
        fail(`\nNode module installation failed.\n    ${err}\n`);
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
function spawnChildProcess(command: string, args: string[], options: any): Promise<boolean> {
    return new Promise((resolve, reject) => {
        const task = spawn(command, args, options);

        task.on("close", code => {
            if (code !== 0) {
                reject(new Error(`command "${command} exited with a non-zero status code."`));
            }
            return resolve(true);
        });

        task.on("error", err => {
            return reject(err);
        });
    });
}
