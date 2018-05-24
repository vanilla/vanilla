/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

const fs = require("fs");
const path = require("path");
const glob = require("glob");
const verifyModuleInstallation = require("./verifyModules");

const VANILLA_ROOT = path.resolve(path.join(__dirname, "../../"));
const TEST_MODULES = path.join(VANILLA_ROOT, "tests/node_modules");

function getTestDirectoriesInDirectory(rootDir) {
    return glob
        .sync(path.join(VANILLA_ROOT, `${rootDir}/*/src/scripts/__tests__`))
        .map(fs.realpathSync) // Resolve symlinks
        .map(dir => dir.replace("/__tests__", "")); // Trim off the test ending
}

const roots = [
    ...getTestDirectoriesInDirectory("applications"),
    ...getTestDirectoriesInDirectory("plugins"),
];

const moduleDirectories = roots
    .map(root => path.normalize(path.join(root, "../../node_modules")));

// Push in the testing node_modules directories so we can resolve our testing utilities.
moduleDirectories.unshift(path.resolve(VANILLA_ROOT, "tests/node_modules"))

// Verify the node_modules have actually been installed everywhere.
moduleDirectories.forEach(verifyModuleInstallation);

const setupFiles = roots.map(root => path.join(root, "__tests__/setup.ts")).filter(fs.existsSync);
const moduleNameMapper = {
    "@dashboard/(.*)$": path.resolve(VANILLA_ROOT, "applications/dashboard/src/scripts/$1"),
    "@vanilla/(.*)$": path.resolve(VANILLA_ROOT, "applications/vanilla/src/scripts/$1"),
};

const babelConfig = JSON.parse(fs.readFileSync(path.resolve(path.join(__dirname, ".babelrc"))));

// We do NOT want to transform any node modules expcept for Quill.
let transformIgnorePatterns = moduleDirectories.map(moduleDir => {
    return fs.readdirSync(moduleDir)
        .filter(dir => !dir.includes("quill"))
        .map(dir => "/node_modules/" + dir + "/");
});

// Flatten
transformIgnorePatterns = [].concat.apply([], transformIgnorePatterns);

console.log(moduleDirectories);

module.exports = {
    roots,
    moduleDirectories,
    moduleNameMapper,
    setupFiles,
    transformIgnorePatterns,
    testPathIgnorePatterns: ["/node_modules/", "/fixtures/", "/bower_components/", "setup.ts"],
    watchPathIgnorePatterns: ["/fixtures/"],
    transform: {
        "^.+\\.(ts|tsx)$": require.resolve("ts-jest"),
        "^.+\\.(js|jsx)$": require.resolve("babel-jest"),
        "^.+\\.svg?$": require.resolve("html-loader-jest"),
    },
    moduleFileExtensions: ["ts", "tsx", "js", "jsx"],
    testRegex: "\\.test\\.(ts|tsx)$",
    globals: {
        "ts-jest": {
            babelConfig,
        },
        VANILLA_ROOT,
    }
};
