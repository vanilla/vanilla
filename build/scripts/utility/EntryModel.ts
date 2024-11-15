/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import fse from "fs-extra";
import * as path from "path";
import { Alias } from "vite";
import {
    DYNAMIC_ENTRY_DIR_PATH,
    LIBRARY_SRC_DIRECTORY,
    PACKAGES_DIRECTORY,
    VANILLA_ADDONS,
    VANILLA_APPS,
    VANILLA_PLUGINS,
    VANILLA_ROOT,
    VANILLA_THEMES,
    VANILLA_THEMES_LEGACY,
} from "../env";

interface IEntry {
    entryPath: string;
    addonPath: string;
}

interface IWebpackEntries {
    [outputName: string]: string;
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

    /**
     * Trigger directory lookups to parse all of the files in the project.
     * This is where ALL files lookups should be started from.
     */
    public constructor() {
        this.initAddons(VANILLA_APPS);
        this.initAddons(VANILLA_PLUGINS);
        this.initAddons(VANILLA_ADDONS);
        this.initAddons(VANILLA_THEMES);
        this.initAddons(VANILLA_THEMES_LEGACY);
        this.initEntries();
    }

    /**
     * Get the production entries.
     *
     * We put all of the addons into a "virtual" entry that's constructed here.
     * This is so the addon chunks can be conditionally applied based off of the runtime.
     *
     * @param section The section to get entries for. These sections are dynamically generated.
     */
    public synthesizeHtmlEntry(outFile: string, sections: string[]) {
        const entryJsFiles: string[] = [];

        for (const section of sections) {
            const entryFile = this.synthesizeJSEntryForSection(section);
            entryJsFiles.push(entryFile);
        }

        const entryJsScriptHtml = entryJsFiles
            .map((file) => {
                return `<script type="module" src="${file}"></script>`;
            })
            .join("\n");

        const synthesizedHtml = `
        <!doctype html>
        <html lang="en">
          <head>
            <meta charset="UTF-8" />
            <link rel="icon" type="image/svg+xml" href="/vite.svg" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Vite + TS</title>
          </head>
          <body>
            <div id="app"></div>
            ${entryJsScriptHtml}
          </body>
        </html>
`;
        fse.writeFileSync(outFile, synthesizedHtml);
    }

    private synthesizeJSEntryForSection(section: string): string {
        // A mapping `import()` strings by addon.
        let dynamicImportStringsByAddonKey: Record<string, string[]> = {};
        let constantImportStrings: string[] = [];

        const constantImportAddons = ["library", "dashboard", "vanilla"];

        // Loop through the addons
        for (const entryDir of this.entryDirs) {
            let importStrings: string[] = [];
            let addonName: string | null = null;
            let constantImportStringsForAddon: string[] = [];

            // The common entry is one shared between sections.
            // An addon may or may not have one.
            const commonEntry = this.lookupEntry(entryDir, "common");
            if (commonEntry !== null) {
                addonName = path.basename(commonEntry.addonPath).toLowerCase();
                importStrings.push(
                    `import(
                        /* webpackChunkName: "addons_${addonName}" */
                        "${commonEntry.entryPath}"
                    ).catch(e => console.error("Error loading javascript for addon '${addonName}'", e))`,
                );
                constantImportStringsForAddon.push(`import "${commonEntry.entryPath}";`);
            }

            // The main entry for the section.
            const entry = this.lookupEntry(entryDir, section);
            if (entry !== null) {
                addonName = path.basename(entry.addonPath).toLowerCase();
                importStrings.push(
                    `import(
                        /* webpackChunkName: "addons_${addonName}" */
                        "${entry.entryPath}"
                    ).catch(e => console.error("Error loading javascript for addon '${addonName}'", e))`,
                );
                constantImportStringsForAddon.push(`import "${entry.entryPath}";`);
            }

            // If we have entries for the addon, stash them.
            if (constantImportAddons.includes(addonName ?? "")) {
                constantImportStrings = [...constantImportStrings, ...constantImportStringsForAddon];
            } else if (importStrings.length > 0 && addonName) {
                dynamicImportStringsByAddonKey[addonName] = importStrings;
            }
        }

        let synthesizedFile = `
import { bootstrapVanilla } from "@library/bootstrap";
${constantImportStrings.join("\n")}


if (import.meta.hot) {
    import.meta.hot.accept((newModule) => {
        console.log("accepting hot module", newModule);
      })
}

const enabledAddonKeys = window.__VANILLA_ENABLED_ADDON_KEYS__;
let addonPromises = [];

${Object.entries(dynamicImportStringsByAddonKey)
    .map(([addonKey, entries]) => {
        return `
if (enabledAddonKeys.includes("${addonKey}")) {
    ${entries.map((entryImport) => `addonPromises.push(${entryImport});`).join("\n")}
}`;
    })
    .join("\n")}

Promise.all(addonPromises).then(async (resolved) => {
    console.log("addon dependencies loaded", resolved.length);
    await bootstrapVanilla();
});
        `;

        // Create the dynamic entry directory if it doesn't exist.
        if (!fse.existsSync(DYNAMIC_ENTRY_DIR_PATH)) {
            fse.mkdirSync(DYNAMIC_ENTRY_DIR_PATH);
        }

        // Write out the dynamic bootstrap file.
        const dynamicBootstrap = path.join(DYNAMIC_ENTRY_DIR_PATH, `${section}.js`);
        fse.writeFileSync(dynamicBootstrap, synthesizedFile);
        return dynamicBootstrap;
    }

    /**
     * Get the directories of all addons being built.
     */
    private get addonDirs(): string[] {
        return Object.values(this.buildAddons!).map((addon) => addon.addonDir);
    }

    /**
     * Get all of the aliases in the project.
     *
     * An alias is a mapping of addonKey -> `@addonKey`.
     * One is generated for every addon being built.
     */
    public get aliases() {
        const result: Alias[] = [];
        for (const addonPath of this.addonDirs) {
            let key = "@" + path.basename(addonPath);
            if (key === "@vanilla") {
                // @vanilla is actually our npm organization so there was a conflict here.
                key = "@vanilla/addon-vanilla";
            }
            result.push({
                find: key,
                replacement: path.resolve(addonPath, "src/scripts"),
            });
        }

        result.push({
            find: "@library",
            replacement: LIBRARY_SRC_DIRECTORY,
        });
        return result;
    }

    /**
     * Initialize buildAddons.
     *
     * This has a lot of FS lookups don't run it more than once.
     *
     * @param rootDir The directory to find addons in.
     */
    private initAddons(rootDir: string) {
        const dirExists = fse.existsSync(path.resolve(rootDir));
        if (!dirExists) {
            return;
        }
        let addonKeyList = fse.readdirSync(path.resolve(rootDir));

        // Go through all of the addons with a `src/scripts` directory and gather data on them.
        for (const addonKey of addonKeyList) {
            const addonPath = path.resolve(rootDir, addonKey);
            const srcPath = path.join(addonPath, "src/scripts");
            const entriesPath = path.join(srcPath, "entries");
            const hasSrcFiles = fse.existsSync(srcPath);
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
    private initEntries() {
        this.entryDirs.push(path.join(LIBRARY_SRC_DIRECTORY, "/entries"));
        for (const addon of Object.values(this.buildAddons)) {
            const entriesExists = fse.existsSync(addon.entriesDir);
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
    private lookupEntry(entryDir: string, section: string): IEntry | null {
        const addonPath = entryDir.replace("/src/scripts/entries", "");
        const tsPath = path.resolve(entryDir, `${section}.ts`);
        const tsPathExists = fse.existsSync(tsPath);

        // Entries can be of .ts or .tsx extensions.
        if (tsPathExists) {
            return {
                addonPath,
                entryPath: tsPath,
            };
        } else {
            const tsxPath = path.resolve(entryDir, `${section}.tsx`);
            const tsxPathExists = fse.existsSync(tsxPath);
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
