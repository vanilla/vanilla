/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import fse from "fs-extra";
import path from "path";
import { VANILLA_ROOT } from "./env";
import { print, printSection } from "./utility/utils";
import { globbySync } from "globby";
import { transformSync } from "esbuild";

const DASHBOARD_ROOT = path.resolve(VANILLA_ROOT);

// Root for replicated minified scripts
const LEGACY_ROOT = path.join(VANILLA_ROOT, "/legacy-dist");

/**
 * Find all the legacy scripts which end in .js or .css
 * @returns string[] - Array of file paths
 */
function getLegacyScripts() {
    printSection("Getting legacy script paths");

    const exclusionDirectories = [
        "bin",
        "node_modules",
        "build",
        "dist",
        "coverage",
        "packages",
        "vendor",
        "storyshots",
        "plugins/hootsuite-app",
        "__tests__",
        "legacy-dist",
    ];
    const exclusionFiles = ["webpack"];

    // Search of all JS and CSS
    print(`Searching for JS and CSS files`);
    const glob = path.join(DASHBOARD_ROOT, "**/*.(js|css)");
    const allFilePaths = globbySync([
        glob,
        ...exclusionDirectories.map((exclusion) => `!${DASHBOARD_ROOT}/${exclusion}/**/*`),
    ]);

    // Do some convenience filtering
    const filteredFilePaths = allFilePaths.filter(
        (file) => !exclusionFiles.some((exclusion) => file.includes(exclusion)) && !file.includes("/src/"),
    );

    const paths = filteredFilePaths.reduce(
        (acc, file) => {
            // Get the extension of the file
            const extension = path.extname(file);
            // Get the directory path
            const scriptDir = path.dirname(file).split(DASHBOARD_ROOT)[1];
            const uniqueDirs = new Set([...acc.directories, scriptDir]);

            return {
                ...acc,
                directories: [...uniqueDirs],
                ...(extension === ".js" && { js: [...acc.js, file] }),
                ...(extension === ".css" && { css: [...acc.css, file] }),
            };
        },
        {
            all: filteredFilePaths,
            js: [],
            css: [],
            directories: [],
        },
    );

    print(
        `📄 Found ${paths.all.length} files\n⚡️ Javascript : ${paths.js.length} files\n🎨 CSS : ${paths.css.length} files\n📁 Unique Directories : ${paths.directories.length} paths`,
    );

    return paths;
}

/**
 * Clean or create legacy-dist directory
 */
function prepareDirectories() {
    print(`Emptying directories`);
    fse.ensureDirSync(LEGACY_ROOT);
    fse.emptyDirSync(LEGACY_ROOT);
}

/**
 * Get the directory path for the script and ensure it exists in the legacy-dist directory
 */
function ensureTargetDirectoryExists(targetPaths: string[]) {
    targetPaths.forEach((targetPath) => {
        const directoryPath = path.join(LEGACY_ROOT, targetPath);
        fse.ensureDirSync(directoryPath);
    });
}

// Minify the legacy javascript
function compressJavascript(pathList: string[]) {
    for (const pathItem of pathList) {
        const scriptContent = fse.readFileSync(pathItem, "utf8");
        try {
            const minified = transformSync(scriptContent, { minify: true, legalComments: "inline" });
            // const minified = minify_sync(scriptContent);
            if (minified.code) {
                const scriptDir = pathItem.split(DASHBOARD_ROOT)[1];
                const legacyFilePath = path.join(LEGACY_ROOT, scriptDir);
                fse.writeFileSync(legacyFilePath, minified.code);
            } else {
                throw new Error("failed to minify code");
            }
        } catch (error) {
            print(`Error minifying ${pathItem}`);
            print(error);
        }
    }
    print(`⚡️ Minified ${pathList.length} javascript files`);
}

// Minify the legacy css
function compressCss(pathList: string[]) {
    pathList.forEach(async (pathItem) => {
        const scriptContent = fse.readFileSync(pathItem, "utf8");
        try {
            const minified = transformSync(scriptContent, { minify: true, loader: "css", legalComments: "inline" });
            // const minified = cssMinifier.minify(scriptContent);
            if (minified.code) {
                const scriptDir = pathItem.split(DASHBOARD_ROOT)[1];
                const legacyFilePath = path.join(LEGACY_ROOT, scriptDir);
                fse.writeFileSync(legacyFilePath, minified.code);
            }
        } catch (error) {
            print(`Error minifying ${pathItem}`);
            print(error);
        }
    });
    print(`🎨 Minified ${pathList.length} css files`);
}

// Write the minified legacy scripts to the legacy-dist build directory
export function minifyScripts() {
    const legacyScripts = getLegacyScripts();
    printSection("Preparing directory structure");
    prepareDirectories();
    ensureTargetDirectoryExists(legacyScripts.directories);
    print(`${legacyScripts.directories.length} Directories created`);

    printSection("Minifying Scripts");
    compressJavascript(legacyScripts.js);
    compressCss(legacyScripts.css);
    print("✔ Minification complete successfully");
}
