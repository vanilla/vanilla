import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import { VANILLA_APPS, VANILLA_ROOT, VANILLA_PLUGINS } from "./vanillaPaths";
import { argv } from "yargs";
const realPath = promisify(fs.realpath);
const readDir = promisify(fs.readdir);
const fileExists = promisify(fs.exists);

export const enum BuildMode {
    TEST = "test",
    DEVELOPMENT = "development",
    PRODUCTION = "production",
    ANALYZE = "analyze",
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

export async function getForumEntries(): Promise<IStringMap> {
    const addonPaths = await lookupAddonPaths();
    const appEntries: IStringMap = {};

    for (const addonPath of addonPaths) {
        const entryType = await addonHasEntry(addonPath, "forum");

        const relativePath = addonPath.replace(VANILLA_ROOT, "") + "/js/webpack/forum";
        if (entryType !== null) {
            appEntries[relativePath] = path.resolve(addonPath, `src/scripts/entries/forum.${entryType}`);
        }
    }

    appEntries["/js/webpack/bootstrap"] = path.resolve(VANILLA_APPS, "dashboard/src/scripts/entries/bootstrap.ts");

    return appEntries;
}
