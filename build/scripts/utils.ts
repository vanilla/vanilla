/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import { VANILLA_APPS, VANILLA_ROOT, VANILLA_PLUGINS, PUBLIC_PATH_SOURCE_FILE, BOOTSTRAP_SOURCE_FILE } from "./env";
import { argv } from "yargs";
const realPath = promisify(fs.realpath);
const readDir = promisify(fs.readdir);
const fileExists = promisify(fs.exists);

export const enum BuildMode {
    TEST = "test",
    DEVELOPMENT = "development",
    PRODUCTION = "production",
    ANALYZE = "analyze",
    POLYFILLS = "polyfills",
}

interface IBuildOptions {
    mode: BuildMode;
}

let cachedAddonPaths: string[] | null = null;

export function getOptions(): IBuildOptions {
    return {
        mode: argv.mode || BuildMode.PRODUCTION,
    };
}

export async function lookupAddonPaths(): Promise<string[]> {
    let addonPaths;
    if (cachedAddonPaths === null) {
        const addonPathsByDir = await Promise.all([lookupAddonType(VANILLA_APPS), lookupAddonType(VANILLA_PLUGINS)]);

        // Merge the arrays together.
        addonPaths = [].concat.apply([], addonPathsByDir);
        cachedAddonPaths = addonPaths;
    } else {
        // Be consistent about being async here.
        addonPaths = await Promise.resolve(cachedAddonPaths);
    }

    return addonPaths;
}

async function lookupAddonType(rootDir: string): Promise<string[]> {
    const addonKeyList = await readDir(path.resolve(rootDir));
    const finalPaths: string[] = [];
    for (const addonKey of addonKeyList) {
        const addonPath = path.resolve(rootDir, addonKey);
        const hasForum = await addonHasEntry(addonPath, "forum");
        if (hasForum) {
            const finalPath = await realPath(addonPath);
            finalPaths.push(finalPath);
        }
    }
    return finalPaths;
}

interface IStringMap {
    [key: string]: string;
}

export async function getAddonAliasMapping(): Promise<IStringMap> {
    const addonPaths = await lookupAddonPaths();
    const result: IStringMap = {};
    for (const addonPath of addonPaths) {
        const key = "@" + path.basename(addonPath);
        result[key] = path.resolve(addonPath, "src/scripts");
    }
    return result;
}

export async function getScriptSourceFiles(): Promise<string[]> {
    const addonPaths = await lookupAddonPaths();
    return addonPaths.map(addonPath => path.resolve(addonPath, "src/scripts"));
}

type EntryType = "ts" | "tsx" | null;

async function addonHasEntry(addonPath: string, entry: "forum" | "dashboard" | "knowledge"): Promise<EntryType> {
    const tsPath = path.resolve(addonPath, `src/scripts/entries/${entry}.ts`);
    const tsPathExists = await fileExists(tsPath);
    if (tsPathExists) {
        return "ts";
    } else {
        const tsxPath = path.resolve(addonPath, `src/scripts/entries/${entry}.tsx`);
        const tsxPathExists = await fileExists(tsxPath);
        if (tsxPathExists) {
            return "tsx";
        }
    }

    return null;
}

function makeEntryPaths(entries: string[]): string[] {
    entries.unshift(PUBLIC_PATH_SOURCE_FILE);
    return entries;
}

function getCommonEntries() {
    return {
        "/js/webpack/bootstrap": makeEntryPaths([BOOTSTRAP_SOURCE_FILE]),
    };
}

export async function getForumEntries(): Promise<any> {
    const addonPaths = await lookupAddonPaths();
    const appEntries: any = {};

    for (const addonPath of addonPaths) {
        const entryType = await addonHasEntry(addonPath, "forum");

        // Strip out the vanilla root to create an "absolute" looking path, from the root of the project.
        const relativePath = addonPath.replace(VANILLA_ROOT, "") + "/js/webpack/forum";
        if (entryType !== null) {
            const entryPath = path.resolve(addonPath, `src/scripts/entries/forum.${entryType}`);
            appEntries[relativePath] = makeEntryPaths([entryPath]);
        }
    }

    return {
        ...appEntries,
        ...getCommonEntries(),
    };
}

export async function getForumHotEntries(): Promise<any> {
    const addonPaths = await lookupAddonPaths();
    const appEntries: string[] = [];

    for (const addonPath of addonPaths) {
        const entryType = await addonHasEntry(addonPath, "forum");

        if (entryType !== null) {
            const entryPath = path.resolve(addonPath, `src/scripts/entries/forum.${entryType}`);
            appEntries.push(entryPath);
        }
    }

    return [...appEntries, BOOTSTRAP_SOURCE_FILE];
}
