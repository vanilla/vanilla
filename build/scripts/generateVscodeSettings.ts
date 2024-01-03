/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import path from "path";
import fse from "fs-extra";
import { VANILLA_ROOT } from "./env";
import { print } from "./utility/utils";

const CLOUD_PLUGINS_DIR = path.join(VANILLA_ROOT, "cloud/plugins");
const VSCODE_SETTINGS = path.resolve(VANILLA_ROOT, ".vscode/settings.json");

async function makeCloudExclusionList(): Promise<string[]> {
    print("☁️ Grabbing cloud addons");
    const addons = await fse.readdir(CLOUD_PLUGINS_DIR);
    return addons.map((addonName) => `plugins/${addonName}`);
}

async function getSettingsJSON(): Promise<{}> {
    print("⚙️ Reading vscode settings");
    return fse.readFile(VSCODE_SETTINGS, "utf8").then((value) => {
        let parsed = {};
        try {
            parsed = JSON.parse(value);
        } catch (e) {
            console.error(e);
        }
        return parsed;
    });
}

function updateSettings(settings: object, exclusionList: string[]) {
    print("⚙️ Generating new vscode settings");
    const uniqueExclusions = [...new Set([...Object.keys(settings["search.exclude"]), ...exclusionList])];
    const newSettings = {
        ...settings,
        "search.exclude": Object.fromEntries(uniqueExclusions.map((exclusion) => [exclusion, true])),
    };
    fse.writeFileSync(VSCODE_SETTINGS, JSON.stringify(newSettings, null, 4));
}

function generateVscodeSettings() {
    Promise.all([makeCloudExclusionList(), getSettingsJSON()]).then(([exclusionList, settings]) => {
        updateSettings(settings, exclusionList);
        print("✅ VScode settings updated");
    });
}

generateVscodeSettings();
