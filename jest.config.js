/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

const fs = require("fs");
const path = require("path");
const glob = require("glob");

function getTestDirectoriesInDirectory(rootDir) {
    return glob
        .sync(path.join(__dirname, `${rootDir}/*/src/scripts/__tests__`))
        .map(fs.realpathSync) // Resolve symlinks
        .map(dir => dir.replace("/__tests__", "")); // Trim off the test ending
}

const roots = [
    ...getTestDirectoriesInDirectory("applications"),
    ...getTestDirectoriesInDirectory("plugins"),
];

const moduleDirectories = roots.map(root => path.normalize(path.join(root, "../../node_modules")));
const setupFiles = roots.map(root => path.join(root, "__tests__/setup.ts")).filter(fs.existsSync);

const moduleNameMapper = {
    "@dashboard/(.*)$": path.resolve(__dirname, "applications/dashboard/src/scripts/$1"),
    "@vanilla/(.*)$": path.resolve(__dirname, "applications/vanilla/src/scripts/$1"),
};

const babelConfig = JSON.parse(fs.readFileSync(path.resolve("./.babelrc")));

const transformIgnorePatterns = fs.readdirSync(path.join(__dirname, "node_modules"))
    .filter(dir => !dir.includes("quill"))
    .map(dir => "node_modules\/" + dir);

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
        "VANILLA_ROOT": path.resolve(__dirname),
    }
};
