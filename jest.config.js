/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

const fs = require("fs");
const path = require("path");
const glob = require("glob");

//  function getTestDirectoriesInDirectory(rootDir) {
//     return glob
//         .sync(path.join(__dirname, `${rootDir}/*/src/scripts/__tests__`))
//         .map(fs.realpathSync) // Resolve symlinks
//         .map(dir => dir.replace("/__tests__", "")); // Trim off the test ending
// }

//  const roots = [
//     ...getTestDirectoriesInDirectory("applications"),
//     ...getTestDirectoriesInDirectory("plugins"),
// ];
const VANILLA_DIR = path.resolve(__dirname);

const moduleDirs = [
    path.join(VANILLA_DIR, "node_modules"),
];
const aliases = {
    "@library/(.*)$": path.join(VANILLA_DIR, "library/src/scripts/$1"),
};
const setupFiles = [
    path.join(VANILLA_DIR, "library/src/scripts/__tests__/setup.ts"),
];

function calcForRoot(root) {
    const rootDir = path.resolve(__dirname, root);
    const addonKeys = fs.readdirSync(rootDir);
    for (const addonKey of addonKeys) {
        const setupFile = path.join(rootDir, addonKey, "src/scripts/__tests__/setup.ts");
        if (!fs.existsSync(setupFile)) {
            continue;
        }

        aliases[`@${addonKey}/(.*)$`] = path.join(rootDir, addonKey, "src/scripts/$1");
        setupFiles.push(setupFile);
        moduleDirs.push(path.join(rootDir, addonKey, "node_modules"));
    }
}

// calcForRoot("applications");
// calcForRoot("plugins");

module.exports = {
    roots: ["library"],
    moduleDirectories: moduleDirs,
    moduleNameMapper: aliases,
    setupFiles,
    transformIgnorePatterns: ["node_modules/(?!(quill)/)", "bower_components"],
    testPathIgnorePatterns: ["/node_modules/", "/fixtures/", "/bower_components/", "setup.ts"],
    watchPathIgnorePatterns: ["/fixtures/"]
};
