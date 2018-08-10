/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import { VANILLA_APPS, VANILLA_ROOT, VANILLA_PLUGINS, PUBLIC_PATH_SOURCE_FILE, BOOTSTRAP_SOURCE_FILE } from "../env";
import { getOptions, BuildMode } from "../options";
const realPath = promisify(fs.realpath);
const readDir = promisify(fs.readdir);
const fileExists = promisify(fs.exists);

const cachedAddonPaths: {
    [section: string]: string[];
} = {};

/**
 * Lookup all of the addon paths that need to be built for a given section. These are cached heavily.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
export async function lookupAddonPaths(section: string): Promise<string[]> {
    let addonPaths: string[];
    if (!(section in cachedAddonPaths)) {
        const addonPathsByDir = await Promise.all([
            lookupAddonType(VANILLA_APPS, section),
            lookupAddonType(VANILLA_PLUGINS, section),
        ]);

        // Merge the arrays together.
        addonPaths = [].concat.apply([], addonPathsByDir);
        cachedAddonPaths[section] = addonPaths;
    } else {
        // Be consistent about being async here.
        addonPaths = await Promise.resolve(cachedAddonPaths[section]);
    }

    // Filter only enabled plugins to build on
    const options = await getOptions();
    if (options.mode === BuildMode.DEVELOPMENT) {
        addonPaths = addonPaths.filter(addonPath => {
            const addonKey = path.basename(addonPath);

            // Check if we have a case-insensitive addon key match.
            return options.enabledAddonKeys.some(val => {
                if (val.toLowerCase() === addonKey.toLowerCase()) {
                    return true;
                }
                return false;
            });
        });
    }

    return addonPaths;
}

/**
 * Get all addons of a certain type.
 *
 * @param rootDir - The root directory to for addons in. Eg. applications, plugins, themes.
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
async function lookupAddonType(rootDir: string, section: string): Promise<string[]> {
    const addonKeyList = await readDir(path.resolve(rootDir));
    const finalPaths: string[] = [];
    for (const addonKey of addonKeyList) {
        const addonPath = path.resolve(rootDir, addonKey);
        const hasSection = await addonHasEntry(addonPath, section);
        const hasBoostrap = await addonHasEntry(addonPath, "bootstrap");
        if (hasSection || hasBoostrap) {
            const finalPath = await realPath(addonPath);
            finalPaths.push(finalPath);
        }
    }
    return finalPaths;
}

interface IStringMap {
    [key: string]: string;
}

/**
 * Generate aliases for the build.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
export async function getAddonAliasMapping(section: string): Promise<IStringMap> {
    const addonPaths = await lookupAddonPaths(section);
    const result: IStringMap = {};
    for (const addonPath of addonPaths) {
        const key = "@" + path.basename(addonPath);
        result[key] = path.resolve(addonPath, "src/scripts");
    }
    return result;
}

/**
 * Get the source file locations for the build.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
export async function getScriptSourceDirectories(section: string): Promise<string[]> {
    const addonPaths = await lookupAddonPaths(section);
    return addonPaths.map(addonPath => path.resolve(addonPath, "src/scripts"));
}

type EntryType = "ts" | "tsx" | null;

/**
 * Determine if an addon has an entry of a particular type. Return that type if applicable.
 *
 * @param addonPath - The path of the addon to look in.
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
async function addonHasEntry(addonPath: string, section: string): Promise<EntryType> {
    const tsPath = path.resolve(addonPath, `src/scripts/entries/${section}.ts`);
    const tsPathExists = await fileExists(tsPath);
    if (tsPathExists) {
        return "ts";
    } else {
        const tsxPath = path.resolve(addonPath, `src/scripts/entries/${section}.tsx`);
        const tsxPathExists = await fileExists(tsxPath);
        if (tsxPathExists) {
            return "tsx";
        }
    }

    return null;
}

/**
 * Push the critical source path entry into each entry. This is needed for dynamic imports to work.
 *
 * @param entries - The entries you want to decorate.
 */
function makeEntryPaths(entries: string[]): string[] {
    entries.unshift(PUBLIC_PATH_SOURCE_FILE);
    return entries;
}

/**
 * Get common entrypoints. These are addon independant.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
function getCommonEntries(section: string) {
    return {
        [`/js/webpack/bootstrap-${section}`]: makeEntryPaths([BOOTSTRAP_SOURCE_FILE]),
    };
}

/**
 * Get all of the webpack entries for a production build.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
export async function getEntries(section: string): Promise<any> {
    const addonPaths = await lookupAddonPaths(section);
    const appEntries: any = {};

    for (const addonPath of addonPaths) {
        const entryType = await addonHasEntry(addonPath, section);

        // Strip out the vanilla root to create an "absolute" looking path, from the root of the project.
        const relativePath = addonPath.replace(VANILLA_ROOT, "") + `/js/webpack/${section}`;
        if (entryType !== null) {
            const entryPath = path.resolve(addonPath, `src/scripts/entries/${section}.${entryType}`);
            appEntries[relativePath] = makeEntryPaths([entryPath]);
        }
    }

    return {
        ...appEntries,
        ...getCommonEntries(section),
    };
}

/**
 * Get the webpack entries for a hot build.
 *
 * @param section - The section of the product being built. Eg. forum / admin / knowledge.
 */
export async function getHotEntries(section: string): Promise<any> {
    const addonPaths = await lookupAddonPaths(section);
    const appEntries: string[] = [];

    for (const addonPath of addonPaths) {
        const entryType = await addonHasEntry(addonPath, section);

        if (entryType !== null) {
            const entryPath = path.resolve(addonPath, `src/scripts/entries/${section}.${entryType}`);
            appEntries.push(entryPath);
        }
    }

    return [...appEntries, BOOTSTRAP_SOURCE_FILE];
}
