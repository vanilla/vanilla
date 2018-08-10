import { promisify } from "util";
import { resolve } from "path";
import { VANILLA_ROOT } from "./env";
import { exec } from "child_process";
const execute = promisify(exec);

let config: any = null;

export async function getVanillaConfig(configName: string) {
    if (config) {
        return await Promise.resolve(config);
    } else {
        const configReaderPath = resolve(__dirname, "./configReader.php");
        const result = await execute(`php ${configReaderPath} ${configName}`);
        config = JSON.parse(result.stdout);
        return config;
    }
}
