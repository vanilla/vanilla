import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import { VANILLA_APPS, VANILLA_PLUGINS, VANILLA_THEMES } from "./vanillaPaths";
const realPath = promisify(fs.realpath);
const readDir = promisify(fs.readdir);

const enum BuildMode {
    TEST = "test",
    DEVELOPMENT = "development",
    PRODUCTION = "production",
}

interface IBuildOptions {
    mode: BuildMode;
}

let cachedAddonPaths: string[] | null = null;

export async function lookupAddonPaths(): Promise<string[]> {
    let addonPaths;
    if (cachedAddonPaths === null) {
        const addonPathsByDir = await Promise.all([lookupAddonType(VANILLA_APPS), lookupAddonType(VANILLA_THEMES)]);

        // Merge the arrays together.
        addonPaths = [].concat.apply([], addonPathsByDir);
        cachedAddonPaths = addonPaths;
    } else {
        // Be consistent about being async here.
        addonPaths = await cachedAddonPaths;
    }

    return addonPaths;
}

async function lookupAddonType(rootDir: string): Promise<string[]> {
    const dirList = await readDir(path.resolve(rootDir));
    const fullPaths = dirList.map(appPath => path.resolve(rootDir, appPath));
    const paths = await Promise.all(fullPaths.map(fullPath => realPath(fullPath)));
    return paths;
}

export async function getAddonAliasMapping(
    options: IBuildOptions,
): Promise<{
    [key: string]: string;
}> {
    const addonPaths = await lookupAddonPaths();
    const result = {};
    for (const addonPath of addonPaths) {
        const key = path.basename(addonPath);
        result[key] = addonPath;
    }
    return result;
}

/**
 * Generate aliases for any required addons.
 *
 * Aliases will always be generated for core, applications/vanilla, and applications/dashboard
 *
 * @param options
 * @param forceAll - Force the function to make aliases for every single addon.
 */
export function getAliasesForRequirements(options: ICliOptions, forceAll = false) {
    const { vanillaDirectory, requiredDirectories, rootDirectories } = options;
    const allDirectories = [...requiredDirectories, ...rootDirectories];

    const allowedKeys = allDirectories.map(dir => {
        return path.basename(dir);
    });

    allowedKeys.push("vanilla", "dashboard");

    const result: any = {};
    ["applications", "addons", "plugins", "themes"].forEach(topDirectory => {
        const fullTopDirectory = path.join(vanillaDirectory, topDirectory);

        if (fs.existsSync(fullTopDirectory)) {
            const subdirs = fs.readdirSync(fullTopDirectory);
            subdirs.forEach(addonKey => {
                const key = `@${addonKey}`;

                const shouldAddResult = !result[key] && (forceAll || allowedKeys.includes(addonKey));
                if (shouldAddResult) {
                    result[key] = path.join(vanillaDirectory, topDirectory, addonKey, "src/scripts");
                }
            });
        }
    });

    const outputString = Object.keys(result).join(chalk.white(", "));
    printVerbose(`Using aliases: ${chalk.yellow(outputString)}`);
    return result;
}
