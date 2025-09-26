/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as path from "path";
import * as fs from "fs";

export function getVanillaSrcDirs() {
    const VANILLA_ROOT = path.resolve(__dirname, "../../../");
    const srcDirs = [
        path.join(VANILLA_ROOT, "library/src/scripts"),
        ...getSrcDirsInVanillaDir(path.join(VANILLA_ROOT, "applications")),
        ...getSrcDirsInVanillaDir(path.join(VANILLA_ROOT, "plugins")),
    ];
    return srcDirs;
}

export function getVanillaInjectables() {
    return getVanillaSrcDirs().flatMap((srcDir) => getInjectablesInDir(srcDir));
}

function getInjectablesInDir(dir) {
    const widgetFragmentsDir = path.join(dir, "widget-fragments");
    if (!fs.existsSync(widgetFragmentsDir)) {
        return [];
    }

    return fs
        .readdirSync(widgetFragmentsDir, { withFileTypes: true })
        .filter((dirent) => {
            if (!dirent.name.endsWith(".injectable.ts") && !dirent.name.endsWith(".injectable.tsx")) {
                return false;
            }

            if (!dirent.isFile()) {
                return false;
            }

            return true;
        })
        .map((dirent) => path.join(dirent.path, dirent.name));
}

function getSrcDirsInVanillaDir(vanillaDir) {
    const subdirs = fs
        .readdirSync(vanillaDir, { withFileTypes: true })
        .filter((dirent) => dirent.isDirectory() || dirent.isSymbolicLink())
        .map((dirent) => path.join(dirent.path, dirent.name, "src/scripts"))
        .filter((dir) => {
            return fs.existsSync(dir);
        });

    return subdirs;
}
