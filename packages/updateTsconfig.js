/**
 * Script to update the tsconfig.json file with references to all packages.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

// @ts-check

const path = require("path");
const fs = require("fs");

const VANILLA_ROOT = path.resolve(__dirname, "../");
const TSCONFIG = path.resolve(VANILLA_ROOT, "tsconfig.json");
const PACKAGE_DIR = path.resolve(VANILLA_ROOT, "packages");

// read the tsconfig.
const tsconfigContents = JSON.parse(fs.readFileSync(TSCONFIG, "utf-8"));

// Add all packages.
fs.readdirSync(PACKAGE_DIR).forEach((dir) => {
    const packageJsonFile = path.resolve(PACKAGE_DIR, dir, "package.json");
    if (fs.existsSync(packageJsonFile)) {
        const packageJson = JSON.parse(fs.readFileSync(packageJsonFile, "utf-8"));
        const name = packageJson.name;
        if (name) {
            // Add it to the tsconfig.
            tsconfigContents.compilerOptions.paths[name] = [`./packages/${dir}/*`];
        }
    }
});

// Write back tsconfig.
fs.writeFileSync(TSCONFIG, JSON.stringify(tsconfigContents, null, 4));
