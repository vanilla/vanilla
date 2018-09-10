/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { promisify } from "util";
import * as fs from "fs";
import * as path from "path";
import {
    VANILLA_APPS,
    VANILLA_PLUGINS,
    PUBLIC_PATH_SOURCE_FILE,
    BOOTSTRAP_SOURCE_FILE,
    LIBRARY_SRC_DIRECTORY,
} from "../env";
import { BuildMode, IBuildOptions } from "../options";
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

/**
 * A model to provide data about entry points to the build.
 */
export default class EntryModel {
    /** Regex to match typescript files. */
    private static TS_REGEX = /\.tsx?$/;

    /** The addons that are being built. */
    private buildAddons: {
        [addonKey: string]: IAddon;
    } = {};

    /** Directories containing entrypoints. */
    private entryDirs: string[] = [];

    /**
     * Construct the EntryModel. Be sure to run the async init() method after constructing.
     */
    constructor(private options: IBuildOptions) {}

    /**
     * Trigger directory lookups to parse all of the files in the project.
     * This is where ALL files lookups should be started from.
     */
    public async init() {
        await Promise.all([this.initAddons(VANILLA_APPS), this.initAddons(VANILLA_PLUGINS)]);
        await this.initEntries();
    }

    /**
     *
     * @param section The section to get entries for. These sections are dynamically generated.
     * @see getSections()
     */
    public async getProdEntries(section: string): Promise<IWebpackEntries> {
        const entries: IWebpackEntries = {};

        for (const entryDir of this.entryDirs) {
            const entry = await this.lookupEntry(entryDir, section);
            if (entry !== null) {
                const addonName = path.basename(entry.addonPath);
                entries[`addons/${addonName}`] = [PUBLIC_PATH_SOURCE_FILE, entry.entryPath];
            }
        }

        entries.bootstrap = [PUBLIC_PATH_SOURCE_FILE, BOOTSTRAP_SOURCE_FILE];
        return entries;
    }

    /**
     * Gather all the sections across every addon. Sections are determined by having
     * an entrypoint in /src/scripts/entries. The filename is the section name without its extension.
     */
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

    /**
     * Get the directories of all addons being built.
     */
    public get addonDirs(): string[] {
        return Object.values(this.buildAddons!).map(addon => addon.addonDir);
    }

    /**
     * Get all of the src directories in the project.
     */
    public get srcDirs(): string[] {
        return [
            LIBRARY_SRC_DIRECTORY, // The library srces are always allowed.
            ...Object.values(this.buildAddons!).map(addon => addon.srcDir),
        ];
    }

    /**
     * Get all of the aliases in the project.
     *
     * An alias is a mapping of addonKey -> `@addonKey`.
     * One is generated for every addon being built.
     */
    public get aliases() {
        const result: IWebpackEntries = {};
        for (const addonPath of this.addonDirs) {
            const key = "@" + path.basename(addonPath);
            result[key] = path.resolve(addonPath, "src/scripts");
        }

        result["@library"] = LIBRARY_SRC_DIRECTORY;
        return result;
    }

    /**
     * Initialize buildAddons.
     *
     * This has a lot of FS lookups don't run it more than once.
     *
     * @param rootDir The directory to find addons in.
     */
    private async initAddons(rootDir: string) {
        let addonKeyList = await readDir(path.resolve(rootDir));

        // Filter only the enabled addons for a development build.
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

        // Go through all of the addons with a `src/scripts` directory and gather data on them.
        for (const addonKey of addonKeyList) {
            const addonPath = path.resolve(rootDir, addonKey);
            const srcPath = path.join(addonPath, "src/scripts");
            const entriesPath = path.join(srcPath, "entries");
            const hasSrcFiles = await fileExists(srcPath);
            if (hasSrcFiles) {
                this.buildAddons[addonKey] = {
                    srcDir: srcPath,
                    addonDir: addonPath,
                    entriesDir: entriesPath,
                };
            }
        }
    }

    /**
     * Look up all of the entry directories. This is quite expensive in terms of IO so don't run it more than once.
     */
    private async initEntries() {
        for (const addon of Object.values(this.buildAddons)) {
            const entriesExists = await fileExists(addon.entriesDir);
            if (entriesExists) {
                this.entryDirs.push(addon.entriesDir);
            }
        }
    }

    /**
     * Determine if an addon has an entry for a particular section.
     *
     * @param entryDir - The entry directory to look in.
     * @param section - The sectionn to look for an entry for.
     *
     * @returns An entry if one was found.
     */
    private async lookupEntry(entryDir: string, section: string): Promise<IEntry | null> {
        const addonPath = entryDir.replace("/src/scripts/entries", "");
        const tsPath = path.resolve(entryDir, `${section}.ts`);
        const tsPathExists = await fileExists(tsPath);

        // Entries can be of .ts or .tsx extensions.
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
