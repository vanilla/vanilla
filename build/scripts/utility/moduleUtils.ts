import { getOptions } from "../options";
import { print, fail } from "./utils";
import { lookupAddonPaths } from "./addonUtils";
import chalk from "chalk";
import { spawn } from "child_process";

const alreadyInstalledDirectories = new Set();

/**
 * Install dependancies for all requirements.
 *
 * @param options
 */
export async function installNodeModules(section: string) {
    const options = await getOptions();

    if (!options.install) {
        return;
    }

    print(`Verifying node_module installation for section ${chalk.yellow(section)}.`);
    const originalDir = process.cwd();

    try {
        const directories = await lookupAddonPaths(section);

        for (const dir of directories) {
            if (!alreadyInstalledDirectories.has(dir)) {
                alreadyInstalledDirectories.add(dir);
                print(`Installing node modules for directory: ${chalk.yellow(dir)}`);
                process.chdir(dir);
                const spawnOptions = options.verbose ? { stdio: "inherit" } : {};
                await spawnChildProcess("yarn", ["install"], spawnOptions);
            }
        }
    } catch (err) {
        fail(`\nNode module installation failed.\n    ${err}\n`);
    }

    process.chdir(originalDir);
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
