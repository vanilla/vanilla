/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import path from "path";
import fse from "fs-extra";
import chalk from "chalk";
import { printSection, print, printError } from "../utility/utils";
import { VANILLA_ADDONS, VANILLA_THEMES, VANILLA_PLUGINS, VANILLA_APPS, VANILLA_THEMES_LEGACY } from "../env";
import { writeConfigTheme } from "../utility/configUtils";

interface IThemeData {
    directory: string;
    themeKey: string;
    themeName: string;
}

const TEMPLATE_DIR = path.resolve(__dirname, "./theme-template");

export async function scaffoldTheme(themeData: IThemeData) {
    validateThemeData(themeData);

    const { directory, themeKey, themeName } = themeData;
    const newThemeDir = path.resolve(directory, themeKey);

    // Copy initial template.
    fse.copySync(TEMPLATE_DIR, newThemeDir);

    // Overwrite name and key.
    const addonJson = fse.readJsonSync(path.resolve(newThemeDir, "addon.json"));
    const packageJson = fse.readJsonSync(path.resolve(newThemeDir, "package.json"));
    addonJson["name"] = themeName;
    addonJson["key"] = themeKey;
    packageJson["name"] = themeKey;

    const options: fse.WriteOptions = {
        spaces: 4,
    };
    fse.writeJsonSync(path.resolve(newThemeDir, "addon.json"), addonJson, options);
    fse.writeJsonSync(path.resolve(newThemeDir, "package.json"), packageJson, options);

    // Try to make a symlink.
    if (newThemeDir.startsWith(VANILLA_THEMES)) {
        print(chalk`{green ✔  Theme was created in core, no symlinks required.}`);
    } else {
        const symlinkPath = path.resolve(VANILLA_THEMES, themeKey);
        fse.ensureSymlinkSync(newThemeDir, symlinkPath);
        print(chalk`{green ✔  Successfuly created new theme {white ${themeName}} at {white ${newThemeDir}}.}`);
    }

    // Write the theme to the config.
    await writeConfigTheme(themeKey);
    print(chalk`{green ✔  Set themeKey {white ${themeKey}} as the active theme in {white config.php}.}`);

    printSection("Next Steps");
    print(
        chalk`To finish installing the new theme, start (or restart) your build with {green yarn build} or {green yarn build:dev}.`,
    );
}

/**
 * Validate theme data.
 *
 * @throws Error If the data is invalid.
 */
export function validateThemeData(themeData: IThemeData) {
    const { directory, themeKey, themeName } = themeData;
    printSection("Validation");
    let hasError = false;

    if (!fse.pathExistsSync(directory)) {
        print(chalk`{red ❌ Provided rootDirectory ${directory} doesn't exist.}`);
        hasError = true;
    } else {
        print(chalk`{green ✔  Directory {white ${directory}} exists.}`);
    }

    const newThemeDir = path.resolve(directory, themeKey);
    if (fse.pathExistsSync(newThemeDir)) {
        print(chalk`{red ❌ New theme path {white ${newThemeDir} already exists}.}`);
        hasError = true;
    }

    if (addonAlreadyExists(themeKey)) {
        print(chalk`{red ❌ Provided theme key {white ${themeKey}} aleady exists.}`);
        hasError = true;
    } else {
        print(chalk`{green ✔  Theme Key {white ${themeKey}} is available.}`);
    }

    if (hasError) {
        process.exit(1);
    }
}

function addonAlreadyExists(addonKey: string) {
    const dirs: string[] = [];
    dirs.push(...fse.readdirSync(VANILLA_THEMES));
    dirs.push(...fse.readdirSync(VANILLA_THEMES_LEGACY));
    dirs.push(...fse.readdirSync(VANILLA_PLUGINS));
    dirs.push(...fse.readdirSync(VANILLA_APPS));

    return dirs.includes(addonKey);
}
