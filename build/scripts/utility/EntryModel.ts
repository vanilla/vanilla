/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { promisify } from "util";
import * as fs from "fs";
import fse from "fs-extra";
import * as path from "path";
import {
    VANILLA_APPS,
    VANILLA_PLUGINS,
    PUBLIC_PATH_SOURCE_FILE,
    LIBRARY_SRC_DIRECTORY,
    PACKAGES_DIRECTORY,
    VANILLA_ROOT,
    VANILLA_THEMES,
    VANILLA_ADDONS,
    VANILLA_THEMES_LEGACY,
    EMOTION_DEV_SPEEDUP_FILE,
    DYNAMIC_ENTRY_DIR_PATH,
} from "../env";
import { BuildMode, IBuildOptions } from "../buildOptions";
const readDir = promisify(fs.readdir);
const fileExists = promisify(fs.exists);

interface IEntry {
    entryPath: string;
    addonPath: string;
}

interface IWebpackEntries {
    [outputName: string]:
        | string
        | string[]
        | {
              import: string | string[];
              dependOn: string | string[];
          };
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
    private static readonly TS_REGEX = /\.tsx?$/;

    /** Name of the section defined from having a "bootstrap.ts(x?) file in entries. " */
    private static readonly BOOTSTRAP_SECTION_NAME = "bootstrap";

    /** Name of the section defined from having a "common.ts(x?) file in entries. " */
    private static readonly COMMON_SECTION_NAME = "common";

    /**
     * These 2 sections are special cases are included in all sections. They are not their own sections by themselves. */
    private excludedSections = [EntryModel.BOOTSTRAP_SECTION_NAME, EntryModel.COMMON_SECTION_NAME];

    /** The addons that are being built. */
    private buildAddons: {
        [addonKey: string]: IAddon;
    } = {};

    /** Directories containing entrypoints. */
    private entryDirs: string[] = [];

    /** Directories of all packages */
    public packageDirs: string[] = [];

    /**
     * Construct the EntryModel. Be sure to run the async init() method after constructing.
     */
    constructor(private options: IBuildOptions) {}

    /**
     * Trigger directory lookups to parse all of the files in the project.
     * This is where ALL files lookups should be started from.
     */
    public async init() {
        await Promise.all([
            this.initAddons(VANILLA_APPS),
            this.initAddons(VANILLA_PLUGINS),
            this.initAddons(VANILLA_ADDONS),
            this.initAddons(VANILLA_THEMES),
            this.initAddons(VANILLA_THEMES_LEGACY),
            this.initPackages(),
        ]);
        await this.initEntries();
    }

    /**
     * Get the production entries.
     *
     * We put all of the addons into a "virtual" entry that's constructed here.
     * This is so the addon chunks can be conditionally applied based off of the runtime.
     *
     * @param section The section to get entries for. These sections are dynamically generated.
     */
    public async getProdEntries(section: string) {
        // A mapping `import()` strings by addon.
        let importStringsByAddonKey: Record<string, string[]> = {};

        // Loop through the addons
        for (const entryDir of this.entryDirs) {
            let importStrings: string[] = [];
            let addonName: string | null = null;

            // The common entry is one shared between sections.
            // An addon may or may not have one.
            const commonEntry = await this.lookupEntry(entryDir, "common");
            if (commonEntry !== null) {
                addonName = path.basename(commonEntry.addonPath).toLowerCase();
                importStrings.push(
                    `import(
                        /* webpackChunkName: "addons/${addonName}-common" */
                        /* webpackPreload: true */
                        "${commonEntry.entryPath}"
                    ).catch(e => console.error("Error loading javascript for addon '${addonName}'", e))`,
                );
            }

            // The main entry for the section.
            const entry = await this.lookupEntry(entryDir, section);
            if (entry !== null) {
                addonName = path.basename(entry.addonPath).toLowerCase();
                importStrings.push(
                    `import(
                        /* webpackChunkName: "addons/${addonName}" */
                        /* webpackPreload: true */
                        "${entry.entryPath}"
                    ).catch(e => console.error("Error loading javascript for addon '${addonName}'", e))`,
                );
            }

            // If we have entries for the addon, stash them.
            if (importStrings.length > 0 && addonName) {
                importStringsByAddonKey[addonName] = importStrings;
            }
        }

        let synthesizedFile = `
import { bootstrapVanilla } from "@library/bootstrap";
import { renderPortals } from "@vanilla/react-utils";

const enabledAddonKeys = window.__VANILLA_ENABLED_ADDON_KEYS__;
let addonPromises = [];

${Object.entries(importStringsByAddonKey)
    .map(([addonKey, entries]) => {
        return `
if (enabledAddonKeys.includes("${addonKey}")) {
    ${entries.map((entryImport) => `addonPromises.push(${entryImport});`).join("\n")}
}`;
    })
    .join("\n")}

Promise.all(addonPromises).then((resolved) => {
    console.log("addon dependencies loaded", resolved.length);
    bootstrapVanilla();
});
        `;

        // Create the dynamic entry directory if it doesn't exist.
        if (!fse.existsSync(DYNAMIC_ENTRY_DIR_PATH)) {
            fse.mkdirSync(DYNAMIC_ENTRY_DIR_PATH);
        }

        // Write out the dynamic bootstrap file.
        const dynamicBootstrap = path.join(DYNAMIC_ENTRY_DIR_PATH, `${section}.js`);
        fse.writeFileSync(dynamicBootstrap, synthesizedFile);

        // Add the entry.
        return {
            bootstrap: [PUBLIC_PATH_SOURCE_FILE, dynamicBootstrap],
        };
    }

    /**
     * Get entries for the development build.
     *
     * Compared to the prod build there is 1 multi-entry instead of multiple.
     * Eg. 1 output bundle per section.
     *
     * @param section - The section to get entries for.
     */
    public async getDevEntries(section: string) {
        const prodEntries = await this.getProdEntries(section);
        prodEntries.bootstrap.shift();
        prodEntries.bootstrap?.unshift(EMOTION_DEV_SPEEDUP_FILE);
        return prodEntries;
    }

    /**
     * Gather all the sections across every addon. Sections are determined by having
     * an entrypoint in /src/scripts/entries. The filename is the section name without its extension.
     */
    public async getSections(): Promise<string[]> {
        let names: string[] = [];
        for (const dir of this.entryDirs) {
            const resolvedPath = path.resolve(dir);
            const dirExists = await fileExists(resolvedPath);
            if (!dirExists) {
                continue;
            }

            const entryNameList = await readDir(resolvedPath);
            names.push(...entryNameList);
        }

        names = names
            .filter((name) => name.match(EntryModel.TS_REGEX))
            .map((name) => name.replace(EntryModel.TS_REGEX, ""))
            // Filter out unwanted sections (special cases).
            .filter((name) => !this.excludedSections.includes(name));

        names = Array.from(new Set(names));

        const { sections } = this.options;
        if (sections != null) {
            names = names.filter((name) => sections.includes(name));
        }

        return names;
    }

    /**
     * Get the directories of all addons being built.
     */
    public get addonDirs(): string[] {
        return Object.values(this.buildAddons!).map((addon) => addon.addonDir);
    }

    /**
     * Get all of the src directories in the project.
     */
    public get srcDirs(): string[] {
        return Object.values(this.buildAddons!).map((addon) => addon.srcDir);
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
            let key = "@" + path.basename(addonPath);
            if (key === "@vanilla") {
                // @vanilla is actually our npm organization so there was a conflict here.
                key = "@vanilla/addon-vanilla";
            }
            result[key] = path.resolve(addonPath, "src/scripts");
        }

        result["@library"] = LIBRARY_SRC_DIRECTORY;
        return result;
    }

    /**
     * Initialize lookups for all file-system modules.
     */
    private async initPackages() {
        const dirNames = await readDir(PACKAGES_DIRECTORY);
        this.packageDirs = [
            ...dirNames.map((name) => path.resolve(PACKAGES_DIRECTORY, name)),
            path.resolve(VANILLA_ROOT, "library"),
        ];
    }

    /**
     * Initialize buildAddons.
     *
     * This has a lot of FS lookups don't run it more than once.
     *
     * @param rootDir The directory to find addons in.
     */
    private async initAddons(rootDir: string) {
        const dirExists = await fileExists(path.resolve(rootDir));
        if (!dirExists) {
            return;
        }
        let addonKeyList = await readDir(path.resolve(rootDir));

        // Filter only the enabled addons for a development build.
        if (this.options.mode === BuildMode.DEVELOPMENT) {
            addonKeyList = addonKeyList.filter((addonPath) => {
                const addonKey = path.basename(addonPath).toLowerCase();

                // Check if we have a case-insensitive addon key match.
                return this.options.enabledAddonKeys.some((val) => {
                    if (val.toLowerCase() === addonKey) {
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
        this.entryDirs.push(path.join(LIBRARY_SRC_DIRECTORY, "/entries"));
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
