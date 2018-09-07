/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import { VANILLA_APPS, VANILLA_ROOT, VANILLA_PLUGINS, PUBLIC_PATH_SOURCE_FILE, BOOTSTRAP_SOURCE_FILE } from "../env";
import { getOptions, BuildMode, IBuildOptions } from "../options";
const realPath = promisify(fs.realpath);
const readDir = promisify(fs.readdir);
const fileExists = promisify(fs.exists);

interface IEntry {
    entryPath: string;
    addonPath: string;
}

interface IWebpackEntries {
    [outputName: string]: string | string[];
}

interface IAddon {
    entriesDir: string;
    srcDir: string;
    addonDir: string;
}

export default class EntryModel {
    private static TS_REGEX = /\.tsx?$/;

    /** This should be changed to a location that is not in dashboard. */
    private static BOOTSTRAP_PATH = path.resolve(VANILLA_APPS, "dashboard/src/scripts/entries/bootstrap.ts");
    private static PUBLIC_PATH_PATH = path.resolve(VANILLA_ROOT, "build/entries/public-path.ts");

    private buildAddons: {
        [addonKey: string]: IAddon;
    } = {};
    private entryDirs: string[] = [];

    constructor(private options: IBuildOptions) {}

    public async init() {
        await Promise.all([this.initAddons(VANILLA_APPS), this.initAddons(VANILLA_PLUGINS)]);
        await this.initEntries();
    }

    public async getProdEntries(section: string): Promise<IWebpackEntries> {
        const entries: IWebpackEntries = {};

        for (const entryDir of this.entryDirs) {
            const entry = await this.getAddonEntry(entryDir, section);
            if (entry !== null) {
                const addonName = path.basename(entry.addonPath);
                entries[`addons/${addonName}`] = [EntryModel.PUBLIC_PATH_PATH, entry.entryPath];
            }
        }

        entries.bootstrap = [EntryModel.PUBLIC_PATH_PATH, EntryModel.BOOTSTRAP_PATH];
        return entries;
    }

    public async getSections(): Promise<string[]> {
        let names: string[] = [];
        for (const dir of this.entryDirs) {
            const entryNameList = await readDir(path.resolve(dir));
            names.push(...entryNameList);
        }

        names = names
            .filter(name => name.match(EntryModel.TS_REGEX))
            .map(name => name.replace(EntryModel.TS_REGEX, ""))
            .filter(name => name !== "bootstrap");

        names = Array.from(new Set(names));

        return names;
    }

    public get addonDirs(): string[] {
        return Object.values(this.buildAddons!).map(addon => addon.addonDir);
    }

    public get srcDirs(): string[] {
        return Object.values(this.buildAddons!).map(addon => addon.srcDir);
    }

    public get aliases() {
        const result: IWebpackEntries = {};
        for (const addonPath of this.addonDirs) {
            const key = "@" + path.basename(addonPath);
            result[key] = path.resolve(addonPath, "src/scripts");
        }
        return result;
    }

    private async initAddons(rootDir: string) {
        let addonKeyList = await readDir(path.resolve(rootDir));

        // Filter only the enabled addons for a Hot Build.
        if (this.options.mode === BuildMode.DEVELOPMENT) {
            addonKeyList = addonKeyList.filter(addonPath => {
                const addonKey = path.basename(addonPath);

                // Check if we have a case-insensitive addon key match.
                return this.options.enabledAddonKeys.some(val => {
                    if (val.toLowerCase() === addonKey.toLowerCase()) {
                        return true;
                    }
                    return false;
                });
            });
        }

        for (const addonKey of addonKeyList) {
            const addonPath = path.resolve(rootDir, addonKey);
            const srcPath = path.join(addonPath, "src/scripts");
            const entriesPath = path.join(srcPath, "entries");
            const hasEntries = await fileExists(srcPath);
            if (hasEntries) {
                this.buildAddons[addonKey] = {
                    srcDir: srcPath,
                    addonDir: addonPath,
                    entriesDir: entriesPath,
                };
            }
        }
    }

    private async initEntries() {
        for (const addon of Object.values(this.buildAddons)) {
            const entriesExists = await fileExists(addon.entriesDir);
            if (entriesExists) {
                this.entryDirs.push(addon.entriesDir);
            }
        }
    }

    /**
     * Determine if an addon has an entry of a particular type. Return that type if applicable.
     *
     * @param addonPath - The path of the addon to look in.
     * @param section - The section of the product being built. Eg. forum / admin / knowledge.
     */
    private async getAddonEntry(entryDir: string, section: string): Promise<IEntry | null> {
        const addonPath = entryDir.replace("/src/scripts/entries", "");
        const tsPath = path.resolve(entryDir, `${section}.ts`);
        const tsPathExists = await fileExists(tsPath);
        if (tsPathExists) {
            return {
                addonPath,
                entryPath: tsPath,
            };
        } else {
            const tsxPath = path.resolve(entryDir, `${section}.tsx`);
            const tsxPathExists = await fileExists(tsxPath);
            if (tsxPathExists) {
                return {
                    addonPath,
                    entryPath: tsxPath,
                };
            }
        }

        return null;
    }
}
