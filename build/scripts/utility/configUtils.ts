/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { promisify } from "util";
import { resolve } from "path";
import { exec } from "child_process";
const execute = promisify(exec);

let config: any = null;

/**
 * Fetch a JSON version of the apps PHP config.
 *
 * @param configName - The name of the config to lookup.
 */
export async function getVanillaConfig(configName: string) {
    if (config) {
        return await Promise.resolve(config);
    } else {
        const configReaderPath = resolve(__dirname, "../configReader.php");
        const result = await execute(`php ${configReaderPath} ${configName}`);
        config = JSON.parse(result.stdout);
        return config;
    }
}

export async function writeConfigTheme(themeKey: string) {
    const configReaderPath = resolve(__dirname, "../configThemeWriter.php");
    await execute(`php ${configReaderPath} ${themeKey}`);
}
